<?php

/**
 * This is the model class for table "usuarios".
 *
 * The followings are the available columns in table 'usuarios':
 * @property integer $id
 * @property string $usuario
 * @property string $nombre
 * @property string $apellido
 * @property string $correo
 * @property string $telefonos
 * @property string $passwd
 * @property string $salt
 * @property string $calle_y_numero
 * @property string $colonia
 * @property string $municipio
 * @property string $estado
 * @property string $cp
 * @property integer $confirmo
 * @property string $fec_alta
 * @property string $fec_act
 *
 * The followings are the available model relations:
 * @property Fotos[] $fotoses
 */
class Usuarios extends CActiveRecord
{
    /**
     * @var string, Verifica si acepto terminos y condiciones
     */
    public $acepto_terminos = false;
    public $confirma_passwd = "";
    public $para_confirmar = false;
    public $cambia_passwd = false;

    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return Usuarios the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'usuarios';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('nombre, apellido, correo, municipio, estado, compromiso, difusion, fecha_nac','required'),
            array('acepto_terminos, passwd, confirma_passwd', 'required', 'on'=>'insert'),
            array('confirmo distribucion', 'numerical', 'integerOnly'=>true),
            array('nombre, apellido, fecha_nac correo, telefonos, passwd, confirma_passwd, salt, municipio, estado', 'length', 'max'=>255),
            array('compromiso', 'safe'),
            array('acepto_terminos', 'acepto_terminos_rule', 'on'=>'insert'),
            array('correo', 'valida_correo', 'on'=>'insert'),
            array('fecha_nac', 'valida_fecha_nac', 'on'=>'insert, update'),
            array('confirma_passwd', 'valida_passwd'),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, nombre, apellido, fecha_nac, correo, telefonos, municipio, estado, confirmo,
				difusion,  fec_alta, fec_act', 'safe', 'on'=>'search'),
        );
    }

    public function acepto_terminos_rule()
    {
        if ($this->acepto_terminos != '1')
            $this->addError($this->acepto_terminos, 'Debes aceptar los términos y condiciones para proseguir');
    }

    public function valida_correo()
    {
        $regex = '/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,3})$/';
        if (!preg_match($regex, $this->correo))
            $this->addError($this->correo, 'El correo no parece válido. Favor de verificar');
        else {
            $correo_existe = $this->model()->findByAttributes(array('correo'=>$this->correo));
            if ($correo_existe != NULL)
                $this->addError($this->correo, 'Ese correo ya fue registrado por alguien más, por favor intenta con otro o recupera tu contraseña desde el inicio de sesión.');
        }
    }

    public function valida_fecha_nac(){
        if($this->cambia_passwd) return true;

        $regex = '/^[0-9]{4}-[0-1][0-9]-[0-3][0-9]$/';
        if(!preg_match($regex, $this->fecha_nac)){
            $this->addError($this->fecha_nac, 'Lo sentimos, la fecha no es válida'.$this->fecha_nac);
            return false;
        }

        $edad = Usuarios::dameEdad($this->fecha_nac);

        if($edad < 6 || $edad > 130){
            $this->addError($this->fecha_nac, 'Lo sentimos, para participar poder participar debes tener entre 6 y 130 años a la fecha del cierre del concurso.');
            return false;
        }
    }

    public static function deboActualizarFechaNac($fecha_nac){
        $edad = Usuarios::dameEdad($fecha_nac);

        return (($edad < 6) || ($edad > 130));
    }

    public static function dameEdad($fecha_nac)
    {
        //Para más allá de php5.3 (te odio ixmati)
        $d1 = new DateTime(Yii::app()->params->fecha_termino);
        $d2 = new DateTime($fecha_nac);
        $diff = $d2->diff($d1);
        return $diff->y;
    }

    public function valida_passwd()
    {
        if(empty($this->para_confirmar) && !$this->cambia_passwd)  //Para evitar cuando se guarda confirmo y la fecha
        {
            if ($this->passwd != $this->confirma_passwd)
                $this->addError($this->passwd, 'La contraseña no coincide con la confirmación.');
        }
    }

    /**
     * (non-PHPdoc)
     * @see CActiveRecord::beforeSave()
     */
    public function beforeSave()
    {
        if ($this->isNewRecord)
        {
            $this->salt = rand()*rand() + rand();
            $this->passwd = md5($this->passwd."|".$this->salt);
        } else {
            if (empty($this->passwd))
            {
                $usuario = $this->findByPk($this->id);
                $this->passwd = $usuario->passwd;
            } else {
                if (empty($this->para_confirmar))  //Para evitar cuando se confirma
                {
                    $this->salt = rand()*rand() + rand();
                    $this->passwd = md5($this->passwd."|".$this->salt);
                }
            }
        }
        return parent::beforeSave();
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'fotos' => array(self::HAS_MANY, 'Fotos', 'usuario_id'),
            'fotos_jovenes' => array(self::HAS_MANY, 'Fotos', 'usuario_id','condition'=>'categoria_id IS NULL'),
            'videos' => array(self::HAS_MANY, 'Videos', 'usuario_id')
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'nombre' => 'Nombre(s)',
            'apellido' => 'Apellido(s)',
            'fecha_nac' => 'Fecha de nacimiento',
            'correo' => 'Correo electrónico',
            'telefonos' => 'Teléfono(s)',
            'passwd' => 'Contraseña',
            'salt' => 'Salt',
            'municipio' => 'Delegación / Municipio',
            'estado' => 'Estado',
            'confirmo' => 'Confirmo',
            'distribucion' => 'Acepto suscripción anual a la revista digital "Espacio Profundo"',
            'fec_alta' => 'Fecha de alta',
            'fec_act' => 'Fecha de última actualización',
            'acepto_terminos' => 'Acepto términos y condiciones',
            'confirma_passwd' => 'Confirma contraseña',
            'compromiso' => 'Compromiso',
            'difusion' => '¿Cómo te enteraste del concurso?'
        );
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     */
    public function search()
    {
        // Warning: Please modify the following code to remove attributes that
        // should not be searched.

        $criteria=new CDbCriteria;

        $criteria->compare('id',$this->id);
        $criteria->compare('nombre',$this->nombre,true);
        $criteria->compare('apellido',$this->apellido,true);
        $criteria->compare('fecha_nac',$this->fecha_nac);
        $criteria->compare('correo',$this->correo,true);
        $criteria->compare('telefonos',$this->telefonos,true);
        $criteria->compare('passwd',$this->passwd,true);
        $criteria->compare('salt',$this->salt,true);
        $criteria->compare('municipio',$this->municipio,true);
        $criteria->compare('estado',$this->estado,true);
        $criteria->compare('confirmo',$this->confirmo);
        $criteria->compare('distribucion',$this->distribucion);
        $criteria->compare('fec_alta',$this->fec_alta,true);
        $criteria->compare('fec_act',$this->fec_act,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }

    public static function estados()
    {
        return array
        (
            'Aguascalientes' => 'Aguascalientes',
            'Baja California' => 'Baja California',
            'Baja California Sur' => 'Baja California Sur',
            'Campeche' => 'Campeche',
            'Chiapas' => 'Chiapas',
            'Chihuahua' => 'Chihuahua',
            'Coahuila' => 'Coahuila',
            'Colima' => 'Colima',
            'Ciudad de México' => 'Ciudad de México',
            'Durango' => 'Durango',
            'Estado de México' => 'Estado de México',
            'Guanajuato' => 'Guanajuato',
            'Guerrero' => 'Guerrero',
            'Hidalgo' => 'Hidalgo',
            'Jalisco' => 'Jalisco',
            'Michoacán' => 'Michoacán',
            'Morelos' => 'Morelos',
            'Nayarit' => 'Nayarit',
            'Nuevo León' => 'Nuevo León',
            'Oaxaca' => 'Oaxaca',
            'Puebla' => 'Puebla',
            'Querétaro' => 'Querétaro',
            'Quintana Roo' => 'Quintana Roo',
            'San Luis Potosí' => 'San Luis Potosí',
            'Sinaloa' => 'Sinaloa',
            'Sonora' => 'Sonora',
            'Tabasco' => 'Tabasco',
            'Tamaulipas' => 'Tamaulipas',
            'Tlaxcala' => 'Tlaxcala',
            'Veracruz' => 'Veracruz',
            'Yucatán' => 'Yucatán',
            'Zacatecas' => 'Zacatecas'
        );
    }

    public static function difusiones()
    {
        return array
        (
            'Redes sociales' => 'Redes sociales',
            'Medios impresos' => 'Medios impresos',
            'Radio' => 'Radio',
            'Televisión' => 'Televisión',
            'Otros' => 'Otros'
        );
    }

    public function send_mail()
    {

        ini_set("SMTP", "xolo.conabio.gob.mx");
        ini_set("sendmail_from", "noreply@conabio.gob.mx");

        $imagen = "<table border=\"0\" align=\"center\" cellpadding=\"0\" cellspacing=\"0\">";
        $imagen.= "<tbody><tr><td align=\"center\" bgcolor=\"#333333\">";
        $imagen.= "<div style=\"background:url('http://www.mosaiconatura.net/img/bg-registro.jpg') no-repeat center center scroll;\"><img src=\"http://www.mosaiconatura.net/img/logo-mosaiconatura.png\" border=\"0\"></div>";
        $imagen.= "</td></tr></tbody></table>";
        $para = $this->correo.", mosaiconatura@conabio.gob.mx";
        $titulo = 'Registro para el '.Yii::app()->name;
        $mensaje = $imagen."<br><br>".$this->nombre.' '.$this->apellido.",";
        $mensaje.= "<br><br>Gracias por completar el registro, para poder acceder necesitas confirmar tu cuenta en el siguiente ";
        $mensaje.= "<a href=\"".Yii::app()->createAbsoluteUrl('usuarios/confirmo')."?id=".$this->id."&fec_alta=".urlencode($this->fec_alta)."\" target=\"_blank\">enlace</a>.";
        $cabeceras = "Content-type: text/html; charset=utf-8"."\r\n";
        $cabeceras.= "From: noreply@conabio.gob.mx"."\r\n";
        mail($para, '=?utf-8?B?'.base64_encode($titulo).'?=', $mensaje, $cabeceras);
    }

    public function send_mail_recupera()
    {
        ini_set("SMTP", "xolo.conabio.gob.mx");
        ini_set("sendmail_from", "noreply@conabio.gob.mx");

        $imagen = "<table border=\"0\" align=\"center\" cellpadding=\"0\" cellspacing=\"0\">";
        $imagen.= "<tbody><tr><td align=\"center\" bgcolor=\"#333333\">";
        $imagen.= "<div style=\"background:url('http://www.mosaiconatura.net/img/bg-registro.jpg') no-repeat center center scroll;\"><img src=\"http://www.mosaiconatura.net/img/logo-mosaiconatura.png\" border=\"0\"></div>";
        $imagen.= "</td></tr></tbody></table>";
        $para = $this->correo.", mosaiconatura@conabio.gob.mx";
        $titulo = 'Recuperar contraseña '.Yii::app()->name;
        $mensaje = $imagen."<br><br>".$this->nombre.' '.$this->apellido.",";
        $mensaje.= "<br><br>Para poder poner una nueva contrase&ntilde;a sigue el siguiente ";
        $mensaje.= "<a href=\"".Yii::app()->createAbsoluteUrl('site/reset')."?id=".$this->id."&fec_alta=".urlencode($this->fec_alta)."\" target=\"_blank\">enlace</a>.";

        $cabeceras = "Content-type: text/html; charset=utf-8"."\r\n";
        $cabeceras.= "From: noreply@conabio.gob.mx"."\r\n";
        mail($para, '=?utf-8?B?'.base64_encode($titulo).'?=', $mensaje, $cabeceras);
    }

    /**
     * Da las categorias que ya no puede tomar (una foto por categoria)
     */
    public function usuarios_categorias()
    {
        $categorias = array();
        foreach ($this->fotos as $f)
        {
            array_push($categorias, $f->categoria->id);
        }
        return $categorias;
    }
    
    public static function dameEstadisticas(){
	    $q = "
SELECT 'Número de fotografías totales' as titulo, COUNT(*) as conteo FROM fotos
union
-- SELECT 'Número de videos totales', COUNT(*) FROM videos
-- union
SELECT 'Número total de registros NUEVOS' as titulo, count(*)  as conteo FROM usuarios WHERE fec_act > '2019-02-08 23:59:30'
union
SELECT 'Registros NUEVOS Adultos' as titulo, count(*)  as conteo FROM usuarios WHERE fec_act > '2019-02-08 23:59:30' AND fecha_nac < '2001-03-10'
union
SELECT 'Registros NUEVOS Jóvenes' as titulo, count(*)  as conteo FROM usuarios WHERE fec_act > '2019-02-08 23:59:30' AND fecha_nac > '2001-03-10' AND fecha_nac < '2013-03-10'
union
SELECT 'Número de participantes NUEVOS confirmados' as titulo, count(*) as conteo FROM usuarios WHERE fec_act > '2019-02-08 23:59:30' AND confirmo=1
union
SELECT  'Adultos NUEVOS confirmados' as titulo, count(*)  as conteo FROM usuarios WHERE fec_act > '2019-02-08 23:59:30' AND fecha_nac < '2001-03-10' AND confirmo=1
union
SELECT 'Jóvenes NUEVOS confirmados' as titulo, count(*)  as conteo FROM usuarios WHERE fec_act> '2019-02-08 23:59:30' AND fecha_nac > '2001-03-10' AND fecha_nac < '2013-03-10' AND confirmo=1
union
SELECT 'Participantes activos con al menos una fotografía o video' as titulo, COUNT(DISTINCT(ids))  as conteo FROM (
SELECT u.id AS ids FROM usuarios u RIGHT JOIN fotos f ON f.usuario_id=u.id UNION
SELECT u.id AS ids FROM usuarios u RIGHT JOIN videos v ON v.usuario_id=u.id) AS ids
union
-- participantes que regresan
select 'Participantes de antiguos concursos que participaron de nuevo' as titulo, count(*)  as conteo from (select count(usuarios.id) from usuarios join fotos on usuarios.id= usuario_id WHERE usuarios.fec_act < '2019-02-08 23:59:30' group by usuarios.id) as t1
union
-- Total de fotografías por categoría (adultos)
select 'Total de fotografías por categoría (adultos)' as titulo,0 as conteo
union
SELECT CONCAT(c.nombre, '') as titulo, COUNT(*)  as conteo FROM fotos f LEFT JOIN categorias c ON c.id=f.categoria_id WHERE categoria_id IS NOT NULL GROUP BY categoria_id
union
select 'Total de fotografías por categoría libre (jovenes)' as titulo,0 as conteo
union
SELECT 'Total de fotografías de jóvenes' as titulo, COUNT(*) as conteo FROM fotos WHERE categoria_id IS NULL
union
select 'Promedio de edades de los participantes' as titulo,0 as conteo
union
SELECT 'Promedio edad de participantes confirmados adultos' as titulo, (2019 - AVG(substring(fecha_nac, 1, 4))) as conteo FROM usuarios WHERE fecha_nac < '2001-03-10' AND usuarios.id IN
((SELECT ids FROM(
SELECT u.id AS ids FROM usuarios u RIGHT JOIN fotos f ON f.usuario_id=u.id UNION
SELECT u.id AS ids FROM usuarios u RIGHT JOIN videos v ON v.usuario_id=u.id) AS ids))
union
SELECT 'Promedio edad de participantes confirmados jóvenes', (2019 - AVG(substring(fecha_nac, 1, 4)))  as conteo FROM usuarios WHERE fecha_nac > '2001-03-10' AND fecha_nac < '2013-03-10' AND usuarios.id IN
((SELECT ids FROM(
SELECT u.id AS ids FROM usuarios u RIGHT JOIN fotos f ON f.usuario_id=u.id UNION
SELECT u.id AS ids FROM usuarios u RIGHT JOIN videos v ON v.usuario_id=u.id) AS ids))
union
-- Participantes activos por estado:
select 'Participantes activos por estado' as titulo,0 as conteo
union
SELECT estado as titulo, count(*) as conteo FROM usuarios WHERE usuarios.id IN
((SELECT ids FROM(
SELECT u.id AS ids FROM usuarios u RIGHT JOIN fotos f ON f.usuario_id=u.id UNION
SELECT u.id AS ids FROM usuarios u RIGHT JOIN videos v ON v.usuario_id=u.id) AS ids)) GROUP BY estado
union
-- Participantes adultos activos por estado:
select 'Participantes adultos activos por estado' as titulo,0 as conteo
union
SELECT estado, count(*) as conteo FROM usuarios WHERE fecha_nac < '2001-03-10'  AND usuarios.id IN
((SELECT ids FROM(
SELECT u.id AS ids FROM usuarios u RIGHT JOIN fotos f ON f.usuario_id=u.id UNION
SELECT u.id AS ids FROM usuarios u RIGHT JOIN videos v ON v.usuario_id=u.id) AS ids)) GROUP BY estado
union
-- Participantes jóvenes activos por estado:
select 'Participantes jóvenes activos por estado',0 as conteo
union
SELECT estado as titulo, count(*) FROM usuarios WHERE fecha_nac > '2001-03-10' AND fecha_nac < '2013-03-10' AND usuarios.id IN
((SELECT ids FROM(
SELECT u.id AS ids FROM usuarios u RIGHT JOIN fotos f ON f.usuario_id=u.id UNION
SELECT u.id AS ids FROM usuarios u RIGHT JOIN videos v ON v.usuario_id=u.id) AS ids)) GROUP BY estado
union
-- medio de difuision
select 'Medio de difusión para participantes NUEVOS' as titulo,0 as conteo
union
select difusion as titulo, count(difusion) as conteo from usuarios WHERE fec_act > '2019-02-08 23:59:30' group by difusion
union
select 'Medio de difusión para participantes con al  menos una fotografía' as titulo,0 as conteo
union
select difusion as titulo, count(*)  as conteo from usuarios WHERE fec_act > '2019-02-08 23:59:30' and id in (select distinct usuario_id from fotos) group by difusion;
";
	    $stats = Yii::app()->db->createCommand($q)->queryAll();
	    return $stats;
    }
}
