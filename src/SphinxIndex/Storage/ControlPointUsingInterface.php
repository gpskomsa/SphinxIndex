<?php

namespace SphinxIndex\Storage;

use SphinxIndex\Service\ControlPointManagerInterface;

/**
 * @todo split interface into two separate: Aware and Mark?
 */
interface ControlPointUsingInterface
{
    /**
     * Sets ConrolPointManager for object
     * @param ControlPointManagerInterface $manager
     */
    public function setControlPointManager(ControlPointManagerInterface $manager);

    /**
     * Sets control point
     */
    public function markControlPoint();
}