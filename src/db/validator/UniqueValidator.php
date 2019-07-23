<?php
/**
 * Created by PhpStorm.
 * User: qv
 * Date: 2018/10/16 0016
 * Time: 10:24
 */

namespace Beauty\db\validator;


class UniqueValidator extends BaseValidator
{

    /**
     * @return bool
     * @throws
     * 检查是否存在
     */
    public function trigger()
    {
        $param = $this->getParams();
        if (empty($param) || !isset($param[$this->field])) {
            return TRUE;
        }

        if (empty($this->model)) {
            return $this->addError('Model error.');
        }

        $model = $this->model;

        $exists = $model::find()->where([$this->field => $param[$this->field]])->exists();
        if ($exists) {
            return $this->addError('The :attribute \'' . $param[$this->field] . '\' is exists!');
        }
        return $this->isFail = TRUE;
    }


}
