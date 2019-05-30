<?php

namespace SphinxIndex\DataProvider;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\ConfigInterface;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\Exception\InvalidServiceException;

class PluginManager extends AbstractPluginManager
{
    /**
     * Instance of DataProviderInterface that ownes this manager
     *
     * @var DataProviderInterface
     */
    protected $dataProvider = null;

    /**
     * Default plugins
     *
     * @var array
     */
    protected $factories = [
        Plugin\Filters::class => InvokableFactory::class,
        Plugin\Rename::class => InvokableFactory::class,
    ];

    /**
     *
     * @var array
     */
    protected $aliases = [
        'Filters' => Plugin\Filters::class,
        'Rename' => Plugin\Rename::class
    ];

    /**
     *
     * @param null|ConfigInterface|ContainerInterface $configOrContainerInstance
     */
    public function __construct($configOrContainerInstance = null)
    {
        parent::__construct($configOrContainerInstance);
        $this->addInitializer(array($this, 'injectDataProvider'));
    }

    /**
     * Injects instance of DataProviderInterface into plugin
     *
     * @param mixed $plugin
     */
    public function injectDataProvider($plugin)
    {
        if (!is_object($plugin)) {
            return;
        }

        $plugin->setDataProvider($this->getDataProvider());
    }

    /**
     *
     * @param DataProviderInterface $dataProvider
     * @return PluginManager
     */
    public function setDataProvider(DataProviderInterface $dataProvider)
    {
        $this->dataProvider = $dataProvider;

        return $this;
    }

    /**
     *
     * @return DataProviderInterface
     */
    public function getDataProvider()
    {
        if (null === $this->dataProvider) {
            throw new \Exception('DataProvider is not set');
        }

        return $this->dataProvider;
    }

    /**
     * Detects if plugin is valid
     *
     * @param mixed $instance
     * @return void
     */
    public function validate($instance)
    {
        if (! is_callable($instance) && ! $instance instanceof Plugin\PluginInterface) {
            throw new InvalidServiceException(
                sprintf(
                    '%s can only create instances of %s and/or callables; %s is invalid',
                    get_class($this),
                    Plugin\PluginInterface::class,
                    (is_object($instance) ? get_class($instance) : gettype($instance))
                )
            );
        }
    }
}