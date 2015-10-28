<?php

namespace SphinxIndex\Storage\ControlPoint;

interface ControlPointManagerInterface
{
    /**
     * Set control point of indexed data
     */
    public function getControlPoint();

    /**
     * Get control point of indexed data
     */
    public function setControlPoint($point);
}