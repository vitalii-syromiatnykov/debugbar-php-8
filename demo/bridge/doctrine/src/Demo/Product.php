<?php

namespace Demo;

/**
 * @Entity @Table(name="products")
 **/
class Product
{
    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /** @Column(type="string") **/
    protected $name;

    #[\ReturnTypeWillChange] public function getId()
    {
        return $this->id;
    }

    #[\ReturnTypeWillChange] public function getName()
    {
        return $this->name;
    }

    #[\ReturnTypeWillChange] public function setName($name): void
    {
        $this->name = $name;
    }
}
