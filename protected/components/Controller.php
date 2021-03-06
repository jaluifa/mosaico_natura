<?php
/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class Controller extends CController
{
    /**
     * @var string the default layout for the controller view. Defaults to '//layouts/column1',
     * meaning using a single column layout. See 'protected/views/layouts/column1.php'.
     */
    public $layout='//layouts/column1';
    /**
     * @var array context menu items. This property will be assigned to {@link CMenu::items}.
     */
    public $menu=array();
    /**
     * @var array the breadcrumbs of the current page. The value of this property will
     * be assigned to {@link CBreadcrumbs::links}. Please refer to {@link CBreadcrumbs::links}
     * for more details on how to specify this property.
     */
    public $breadcrumbs=array();

    /**
     *
     * @return el campo fec_alta de cada tabla
     */
    public static function fechaAlta()
    {
        date_default_timezone_set("Mexico/General");
        return date("Y-m-d H:i:s");
    }

    public function dameInfoUsuario()
    {
        $this->verificaLogin();

        $results = Yii::app()->db->createCommand()
            ->select('r.*')
            ->from('usuarios u')
            ->leftJoin('roles r', 'u.roles_id=r.id')
            ->where('u.id='.Yii::app()->user->id_usuario)
            ->queryRow();

        return $results;
    }

    /**
     * Pone el Id de la sesion en el objeto persistente de yii (cookie)
     * @param string $usuario el nombre
     */
    public function setIdUsuario($usuario)
    {
        $model = Usuarios::model()->findByAttributes(array('usuario'=>$usuario));
        if ($model == NULL)
            $model = Usuarios::model()->findByAttributes(array('correo'=>$usuario));
        Yii::app()->user->setState('id_usuario', $model->id);
    }

    /**
     *
     * @param boolean $dameId si queremos que retorne el id
     */
    public function verificaLogin($dameId=null)
    {
        if(isset(Yii::app()->user->id_usuario))
        {
            if ($dameId)
                return Yii::app()->user->id_usuario;

        } else {
            if (isset(Yii::app()->user->id)) {

                $this->setIdUsuario(Yii::app()->user->id);

                if ($dameId)
                    return $this->verificaLogin(true);
            } else {

            }
        }
    }

    public function vigencia($tipo=null)
    {
        $fecha = date("YmdHis");

        /*Cuando se le ocurra pedir q niños abra a diferente fecha, generar dinamicamente la variable de params*/
        switch($tipo){
            case 'video':
                $fecha_inicio =  Yii::app()->params->fecha_inicio_video;
                $fecha_termino =  Yii::app()->params->fecha_termino_video;
                $msg = ' para VIDEOS ';
                break;
            case 'foto':
                $fecha_inicio =  Yii::app()->params->fecha_inicio_foto;
                $fecha_termino =  Yii::app()->params->fecha_termino_foto;
                $msg = ' para FOTOS ';
                break;
            default:
                $fecha_inicio =  Yii::app()->params->fecha_inicio;
                $fecha_termino =  Yii::app()->params->fecha_termino;
                $msg = ' ';
                break;
        }

        if ($fecha < $fecha_termino && $fecha_inicio < $fecha) {
            return true;
        }else {
            $fec_inicio = new DateTime($fecha_inicio);
            $fec_termino = new DateTime($fecha_termino);
            throw new CHttpException(NULL, "El tiempo del consurso".$msg."ha terminado: (".$fec_inicio->format('d-M-Y H:i')."hrs. al ".$fec_termino->format('d-M-Y H:i')."hrs.)");
        }
    }
}