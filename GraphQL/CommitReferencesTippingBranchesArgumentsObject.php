<?php

namespace GraphQL\SchemaObject;

class CommitReferencesTippingBranchesArgumentsObject extends ArgumentsObject
{
    protected $limit;

    public function setLimit($limit)
    {
        $this->limit = $limit;

        return $this;
    }
}