<?php

namespace Malik12tree\ZATCA;

abstract class EGSDatabase
{
    /**
     * @param EGS $egs
     */
    abstract public function save($egs);

    /**
     * @param string $uuid
     * @param bool   $assignDatabase if true, the database will be assigned to the EGS object
     *
     * @return null|EGS
     */
    public function load($uuid, $assignDatabase = true)
    {
        $egs = $this->_load($uuid);
        if ($egs && $assignDatabase) {
            $egs->setDatabase($this);
        }

        return $egs;
    }

    /**
     * @param string $uuid
     *
     * @return null|EGS
     */
    abstract protected function _load($uuid);
}
