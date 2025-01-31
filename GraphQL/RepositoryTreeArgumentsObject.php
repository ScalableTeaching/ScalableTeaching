<?php

namespace GraphQL\SchemaObject;

use GraphQL\RawObject;

class RepositoryTreeArgumentsObject extends ArgumentsObject
{
    protected $path;
    protected $recursive;
    protected $ref;
    protected $refType;

    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    public function setRecursive($recursive)
    {
        $this->recursive = $recursive;

        return $this;
    }

    public function setRef($ref)
    {
        $this->ref = $ref;

        return $this;
    }

    public function setRefType($refType)
    {
        $this->refType = new RawObject($refType);

        return $this;
    }
}
