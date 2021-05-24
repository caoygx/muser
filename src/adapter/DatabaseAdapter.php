<?php

namespace muser\adapter;


class DatabaseAdapter
{

    protected $model;

    /**
     * the DatabaseAdapter constructor.
     *
     * @param Rule $model
     */
    public function __construct( $model)
    {
        $this->model = $model;
    }

}
