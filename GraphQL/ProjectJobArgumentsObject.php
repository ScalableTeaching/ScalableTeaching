<?php

namespace GraphQL\SchemaObject;

class ProjectJobArgumentsObject extends ArgumentsObject
{
    protected $id;

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}
