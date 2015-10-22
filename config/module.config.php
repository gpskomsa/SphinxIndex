<?php

return array(
    'console' => array(
        'router' => array(
            'routes' => array(
                'index_set' => array(
                    'type' => 'simple',
                    'options' => array(
                        'route'    => 'index (build|update):command <index>',
                        'defaults' => array(
                            '__NAMESPACE__' => 'Index\Controller',
                            'controller'    => 'Index\Index',
                            'action'        => 'index',
                        ),
                    ),
                ),
                'index_split' => array(
                    'type' => 'simple',
                    'options' => array(
                        'route'    => 'index split <index> [<target>]',
                        'defaults' => array(
                            '__NAMESPACE__' => 'Index\Controller',
                            'controller'    => 'Index\Split',
                            'action'        => 'split',
                        ),
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'sphinx_index' => __DIR__ . '/../view',
        ),
    ),
);
