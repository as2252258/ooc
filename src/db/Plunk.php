<?php
/**
 * Created by PhpStorm.
 * User: qv
 * Date: 2018/10/30 0030
 * Time: 16:03
 */

namespace Beauty\db;


use Beauty\base\Component;

class Plunk extends Component
{

    public $query;

    public function __construct(ActiveQuery $query, array $config = [])
    {
        $this->query = $query;
        parent::__construct($config);
    }

    public function call(callable $callback)
    {
        while (count($sql = $this->query->all()) == $this->query->limit) {
            call_user_func($callback, $sql);
            $this->query->offset++;
        }

        return TRUE;
    }

}
