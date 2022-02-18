<?php

namespace GraphQL\SchemaObject;

class GroupMemberEdgeQueryObject extends QueryObject
{
    const OBJECT_NAME = "GroupMemberEdge";

    public function selectCursor()
    {
        $this->selectField("cursor");

        return $this;
    }

    public function selectNode(GroupMemberEdgeNodeArgumentsObject $argsObject = null)
    {
        $object = new GroupMemberQueryObject("node");
        if ($argsObject !== null) {
            $object->appendArguments($argsObject->toArray());
        }
        $this->selectField($object);

        return $object;
    }
}
