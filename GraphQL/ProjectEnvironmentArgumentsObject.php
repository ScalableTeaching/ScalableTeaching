<?php

namespace GraphQL\SchemaObject;

class ProjectEnvironmentArgumentsObject extends ArgumentsObject
{
    protected $name;
    protected $search;
    protected $states;

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function setSearch($search)
    {
        $this->search = $search;

        return $this;
    }

    public function setStates(array $states)
    {
        $this->states = $states;

        return $this;
    }
}
