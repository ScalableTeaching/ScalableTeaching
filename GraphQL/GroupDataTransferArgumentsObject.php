<?php

namespace GraphQL\SchemaObject;

class GroupDataTransferArgumentsObject extends ArgumentsObject
{
    protected $from;
    protected $to;

    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
    }

    public function setTo($to)
    {
        $this->to = $to;

        return $this;
    }
}