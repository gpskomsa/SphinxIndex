<?php

namespace SphinxIndex\DataProvider;

use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\ConfigInterface;

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
    protected $invokableClasses = array(
        'filters'  => 'SphinxIndex\DataProvider\Plugin\Filters',
    );

    /**
     *
     * @param ConfigInterface $configuration
     */
    public function __construct(ConfigInterface $configuration = null)
    {
        parent::__construct($configuration);
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
     * @param mixed $plugin
     * @return void
     */
    public function validatePlugin($plugin)
    {
        if ($plugin instanceof Plugin\PluginInterface) {
            return;
        }

        throw new \Exception(
            sprintf(
                'Plugin of type %s is invalid; must implement %s\Plugin\PluginInterface',
                (is_object($plugin) ? get_class($plugin) : gettype($plugin)),
                __NAMESPACE__
            )
        );
    }
}