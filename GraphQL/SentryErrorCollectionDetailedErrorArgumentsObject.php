<?php

namespace GraphQL\SchemaObject;

class SentryErrorCollectionDetailedErrorArgumentsObject extends ArgumentsObject
{
    protected $id;

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}
