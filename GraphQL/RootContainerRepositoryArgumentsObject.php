<?php

namespace GraphQL\SchemaObject;

class RootContainerRepositoryArgumentsObject extends ArgumentsObject
{
    protected $id;

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}
