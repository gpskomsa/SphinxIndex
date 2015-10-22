<?php

namespace SphinxIndex\Storage\Redis;

use SphinxIndex\Storage\StorageInterface;
use SphinxIndex\Storage\RangedInterface;
use SphinxIndex\Service\RangeProviderInterface;
use SphinxIndex\Storage\Stated;
use SphinxIndex\Storage\Chunks;

use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;
use Zend\Filter\FilterInterface;

class SimpleStorage implements StorageInterface, RangedInterface
{
    use Stated;

    /**
     * The way to store keys in redis
     */
    const LISTKEY_TYPE_ZSETS = 'zsets';
    const LISTKEY_TYPE_LIST = 'list';

    /**
     * How the data stores in redis:
     */
    /**
     * Each key in list(LISTKEY_TYPE_*) is a redis key that holds data
     */
    const KEY_TYPE_KEY = 'key';
    /**
     * Each key in list(LISTKEY_TYPE_*) is a redis hash that holds data
     */
    const KEY_TYPE_HASH = 'hash';
    /**
     * Each key in list(LISTKEY_TYPE_*) holds hothing anywhere, just data itself
     */
    const KEY_TYPE_NONE = 'none';

    /**
     * What is the key in list(LISTKEY_TYPE_*)?
     */
    /**
     * Each key in list(LISTKEY_TYPE_*) is some string and we must do something
     * with it to get real redis key (see toKeyFilters)
     */
    const KEY_IN_LIST_TYPE_FIELD = 'field';
    /**
     * Each key in list(LISTKEY_TYPE_*) is real redis key
     */
    const KEY_IN_LIST_TYPE_KEY = 'key';

    /**
     *
     * @var \Redis
     */
    protected $adapter = null;

    /**
     *
     * @var string
     */
    protected $storageListKeyType = self::LISTKEY_TYPE_ZSETS;

    /**
     *
     * @var string
     */
    protected $storageKeyType = self::KEY_TYPE_KEY;

    /**
     *
     * @var self::KEY_IN_LIST_TYPE_FIELD|self::KEY_IN_LIST_TYPE_KEY
     */
    protected $keyInListType = self::KEY_IN_LIST_TYPE_KEY;

    /**
     * Redis key that point to list of keys(LISTKEY_TYPE_*)
     *
     * @var string
     */
    protected $listKey = null;

    /**
     * Redis prefix for key stored in list(LISTKEY_TYPE_*)
     *
     * @var string
     */
    protected $keyPrefix = null;

    /**
     * Array of filters for KEY_IN_LIST_TYPE_FIELD
     *
     * @var array
     */
    protected $toKeyFilters = array();

    /**
     * Count of rows of data requested from Redis at once
     *
     * @var integer
     */
    protected $poolSize = 10000;

    /**
     *
     * If you need get value of key that is in list(LISTKEY_TYPE_*)
     * within document data(as one of fields) just define value of this property
     *
     * @var string|null
     */
    protected $keyField = null;

    /**
     * We got data for some key from list(LISTKEY_TYPE_*),
     * must we unserialize this data by hand?
     *
     * @var callable|null
     */
    protected $unserializer = null;

    /**
     * We got data for some key in list(LISTKEY_TYPE_*) as array
     * and we need replace keys in that array with new values.
     * So valueMap's value is:
     *
     * array(
     *     0     => 'key_1',
     *     1     => 'key_2',
     *     'key' => 'another_key'
     * )
     *
     * @var array
     */
    protected $valueMap = array();

    /**
     *
     * @var RangeProviderInterface
     */
    protected $ranger = null;

    /**
     * Must the last chunk be empty while splitting data
     *
     * @var boolean
     */
    protected $emptyLastRange = false;

    /**
     *
     * @var EventManagerInterface
     */
    protected $events = null;

    /**
     *
     * @param \Redis $adapter
     * @param array $options
     */
    public function __construct(
        \Redis $adapter,
        array $options = array()
    )
    {
        $this->setAdapter($adapter);
        unset($options['adapter']);

        $this->setOptions($options);
    }

    /**
     *
     * @param array $options
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                call_user_func_array(array($this, $method), array($value));
            }
        }
    }

    /**
     *
     *
     * @param string $value
     * @return SimpleStorage
     */
    public function setStorageListKeyType($value)
    {
        $this->storageListKeyType = $value;

        return $this;
    }

    /**
     *
     * @param string $value
     * @return SimpleStorage
     */
    public function setStorageKeyType($value)
    {
        $this->storageKeyType = $value;

        return $this;
    }

    /**
     *
     * @param array $filters
     * @return SimpleStorage
     */
    public function setToKeyFilters(array $filters)
    {
        foreach ($filters as &$filter) {
            if (is_array($filter)) {
                $name = array_shift($filter);

                $ref = new \ReflectionClass($name);
                $filter = $ref->newInstanceArgs($filter);
            } elseif (is_string($filter)) {
                $filter = new $filter;
            }

            if (!is_object($filter)) {
                throw new \Exception('invalid type of filter');
            }

            if (!$filter instanceof FilterInterface) {
                throw new \Exception('filter must implement FilterInterface');
            }
        }

        $this->toKeyFilters = $filters;

        return $this;
    }

    /**
     *
     * @param integer $value
     * @return \Index\Storage\Redis\SimpleStorage
     */
    public function setPoolSize($value)
    {
        $this->poolSize = (integer) $value;

        return $this;
    }

    /**
     *
     * @param string $value
     * @return SimpleStorage
     */
    public function setListKey($value)
    {
        $this->listKey = $value;

        return $this;
    }

    /**
     *
     * @param mixed $value
     * @return SimpleStorage
     */
    public function setKeyInListType($value)
    {
        $this->keyInListType = $value;

        return $this;
    }

    /**
     *
     * @param string $value
     * @return SimpleStorage
     */
    public function setKeyPrefix($value)
    {
        $this->keyPrefix = $value;

        return $this;
    }

    /**
     *
     * @param integer $value
     * @return SimpleStorage
     */
    public function setMgetSize($value)
    {
        $this->mgetSize = $value;

        return $this;
    }

    /**
     *
     * @param mixed $value
     * @return SimpleStorage
     */
    public function setUnserializer($value)
    {
        $this->unserializer = $value;

        return $this;
    }

    /**
     *
     * @param array $map
     * @return SimpleStorage
     */
    public function setValueMap(array $value)
    {
        $this->valueMap = $value;

        return $this;
    }

    /**
     *
     * @param boolean $value
     * @return SimpleStorage
     */
    public function setEmptyLastRange($value)
    {
        $this->emptyLastRange = (boolean) $value;

        return $this;
    }

    /**
     *
     * @param \Redis $adapter
     * @return SimpleStorage
     */
    public function setAdapter(\Redis $adapter)
    {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     *
     * @param string $keyField
     * @return SimpleStorage
     */
    public function setKeyField($keyField)
    {
        $this->keyField = $keyField;

        return $this;
    }

    /**
     *
     * @return EventManagerInterface
     */
    public function getEventManager()
    {
        if (null === $this->events) {
            $this->setEventManager(new EventManager());
        }

        return $this->events;
    }

    /**
     *
     * @param EventManagerInterface $events
     * @return SimpleStorage
     */
    public function setEventManager(EventManagerInterface $events)
    {
        $events->setIdentifiers(array(
            __CLASS__,
            get_called_class(),
        ));

        $this->events = $events;

        return $this;
    }

    /**
     *
     * @param RangeProviderInterface $ranger
     * @return SimpleStorage
     */
    public function setRangeProvider(RangeProviderInterface $ranger)
    {
        $this->ranger = $ranger;

        return $this;
    }

    /**
     *
     * @param string $name
     * @return mixed
     */
    public function getProperty($name)
    {
        if (!property_exists($this, $name) || null === $this->{$name}) {
            throw new \Exception("$name is not defined");
        }

        return $this->{$name};
    }

    /**
     *
     * @param integer $count
     */
    public function split($count)
    {
        $name = $this->getProperty('listKey');
        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this, compact('count', 'name'));

        $total = $this->getTotalCount();
        $countForChunk = ceil($total/($this->emptyLastRange ? ($count - 1) : $count));
        if ($countForChunk < 1) {
            $countForChunk = 1;
        }

        $ranges = new Chunks();
        for ($i = 0; $i < ($count - 1); $i++) {
            $ranges[] = array(
                $i * $countForChunk,
                ($i * $countForChunk + $countForChunk < $total) ? ($i * $countForChunk + $countForChunk) : $total
            );
        }

        $last = $ranges[count($ranges) - 1];
        $ranges[] = array($last[1], -1);

        $this->ranger->setRange($ranges);

        $emptyLastRange = $this->emptyLastRange;
        $this->getEventManager()->trigger(
            __FUNCTION__ . '.post', $this, compact('count','name','countForChunk','emptyLastRange')
        );
    }

    /**
     *
     * @return Array|false
     */
    public function getItems()
    {
        $currentStart = $this->state(__FUNCTION__);
        if (false === $currentStart) {
            $currentStart = 0;
        }

        $count = $this->getTotalCount();
        $range = $this->ranger ? $this->ranger->getRange() : array(0, -1);

        if ($currentStart) {
            $start = $currentStart;
        } else {
            $start = $range[0];
        }

        $end = $start + $this->poolSize;
        $end = ($range[1] == -1)
            ? ($end >= $count ? $count : $end)
            : ($end >= $range[1] ? $range[1] : $end);
        $end -= 1;

        if ($start >= $count) {
            $this->state(__FUNCTION__, 0);
            return false;
        }

        $keys = $this->getKeys($start, $end);

        $serialized = false;
        if (null !== $this->unserializer
            && is_callable($this->unserializer)) {
            $serialized = true;
        }

        if ($this->keyInListType === self::KEY_IN_LIST_TYPE_FIELD) {
            $pool = $this->getDataForKeys(array_map(array($this, 'valueToKey'), $keys));
        } else {
            $pool = $this->getDataForKeys($keys);
        }

        foreach ($keys as $index => &$item) {
            $key = $item;

            if (false === $pool
                || !isset($pool[$index])
                || false === $pool[$index]) {
                if ($this->storageKeyType != self::KEY_TYPE_NONE) {
                    unset($keys[$index]);
                    continue;
                }

                $item = array();
            } else {
                if ($serialized) {
                    try {
                        $item = @call_user_func_array($this->unserializer, array($pool[$index]));
                    } catch (\Exception $e) {
                        unset($keys[$index]);
                        continue;
                    }

                    if (false === $item) {
                        unset($keys[$index]);
                        continue;
                    }

                    $item = (array) $item;

                    if (count($item)) {
                        $item = array_combine(array_keys($item), array_values($item));
                    }
                } else {
                    $item = $pool[$index];
                }

                foreach ($this->valueMap as $old => $new) {
                    if (array_key_exists($old, $item)) {
                        $item[$new] = $item[$old];
                        unset($item[$old]);
                    } else {
                        $item[$new] = null;
                    }
                }
            }

            if (!isset($item['id'])) {
                $item['id'] = $start + $index + 1;
            }

            if (null !== $this->keyField) {
                $item[$this->keyField] = $key;
            }
        }

        $this->state(__FUNCTION__, $end + 1);

        return $keys;
    }

    /**
     * Returns keys from list(LISTKEY_TYPE_*)
     *
     * @param integer $start
     * @param integer $end
     * @return array
     */
    protected function &getKeys($start, $end)
    {
        switch ($this->storageListKeyType) {
            case self::LISTKEY_TYPE_ZSETS:
                $elements = $this->adapter->zRange(
                    $this->getProperty('listKey'),
                    $start,
                    $end
                );
                break;
            case self::LISTKEY_TYPE_LIST:
                $elements = $this->adapter->lRange(
                    $this->getProperty('listKey'),
                    $start,
                    $end
                );
                break;
            default:
                throw new \Exception("unknown list key type: $this->storageListKeyType");
                break;
        }

        return $elements;
    }

    /**
     * Returns data for passed keys
     *
     * @param array $keys
     * @return array|false
     */
    protected function &getDataForKeys($keys)
    {
        switch ($this->storageKeyType) {
            case self::KEY_TYPE_KEY:
                $data = $this->adapter->mGet($keys);
                break;
            case self::KEY_TYPE_HASH:
                $this->adapter->multi();
                foreach ($keys as $key) {
                    $this->adapter->hGetAll($key);
                }
                $data = $this->adapter->exec();
                break;
            case self::KEY_TYPE_NONE:
                $data = false;
                break;
            default:
                throw new \Exception("unknown key type: $this->storageKeyType");
                break;
        }

        return $data;
    }

    /**
     * Returns total count of documents can be indexed
     *
     * @return integer
     */
    protected function getTotalCount()
    {
        switch ($this->storageListKeyType) {
            case self::LISTKEY_TYPE_ZSETS:
                $count = $this->adapter->zCard($this->getProperty('listKey'));
                break;
            case self::LISTKEY_TYPE_LIST:
                $count = $this->adapter->lLen($this->getProperty('listKey'));
                break;
            default:
                throw new \Exception("unknown list key type: $this->storageListKeyType");
                break;
        }

        return $count;
    }

    /**
     * See $toKeyFilters
     *
     * @param string $value
     * @return string|false
     */
    public function valueToKey($value)
    {
        if ($value === '' || null === $value) {
            return false;
        }

        foreach ($this->toKeyFilters as $filter) {
            $value = $filter->filter($value);
        }

        $prefix = $this->keyPrefix ?: '';
        return $prefix . $value;
    }

    /**
     * @todo Realize it
     */
    public function getItemsToUpdate()
    {
        throw new \Exception('not implemented yet');
    }

    /**
     * @todo Realize it
     */
    public function getItemsToDelete()
    {
        throw new \Exception('not implemented yet');
    }
}