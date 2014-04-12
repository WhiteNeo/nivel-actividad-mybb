<?php
/* 
 * Plataforma: MyBB 1.6.x
 * Autor: Dark Neo
 * Plugin: Nivel de Actividad
 * version: 1.2
 * 
 */

// Inhabilitar acceso directo a este archivo
if(!defined("IN_MYBB"))
{
	die("No puedes ver el contenido de este archivo directamente.");
}

// Añadir hooks
$plugins->add_hook("postbit", "nivel_actividad_postbit");
$plugins->add_hook("postbit_pm", "nivel_actividad_postbit");

// Plugin Info
function nivel_actividad_info()
{
	return array(
		"name"			=> "Nivel de Actividad",
		"description"	=> "Crea unas barras de actividad en el postbit.",
		"website"		=> "http://darkneo.skn1.com",
		"author"		=> "Dark Neo",
		"authorsite"	=> "http://darkneo.skn1.com",
		"version"		=> "1.2",
	);
}


//Se ejecuta al activar el plugin
function nivel_actividad_activate(){
    //Variables que vamos a utilizar
	global $mybb, $cache, $db;

    // Crear el grupo de opciones
    $query = $db->simple_select("settinggroups", "COUNT(*) as rows");
    $rows = $db->fetch_field($query, "rows");

    $new_groupconfig = array(
        'name' => 'nivel_actividad',
        'title' => '[Plugin] Nivel de Actividad',
        'description' => 'Configurar Opciones',
        'disporder' => $rows+1,
        'isdefault' => 0
    );

    $group['gid'] = $db->insert_query("settinggroups", $new_groupconfig);

    // Crear las opciones del plugin a utilizar
    $new_config = array();

    $new_config[] = array(
        'name' => 'nivel_actividad_active',
        'title' => 'Activar el Plugin',
        'description' => 'Selecciona si deseas que este plugin este activo',
        'optionscode' => 'yesno',
        'value' => '1',
        'disporder' => 10,
        'gid' => $group['gid']
    );
    
    foreach($new_config as $array => $content)
    {
        $db->insert_query("settings", $content);
    }

    rebuild_settings();

    //Archivo requerido para reemplazo de templates
	require "../inc/adminfunctions_templates.php";
    //Reemplazos que vamos a hacer en las plantillas 1.- Platilla 2.- Contenido a Reemplazar 3.- Contenido que reemplaza lo anterior
    find_replace_templatesets("postbit", '#'.preg_quote('{$post[\'user_details\']}').'#', '{$post[\'user_details\']}{$post[\'nivel_actividad\']}');
    find_replace_templatesets("postbit_classic", '#'.preg_quote('{$post[\'user_details\']}').'#', '{$post[\'user_details\']}{$post[\'nivel_actividad\']}');    
    //Se actualiza la info de las plantillas
	$cache->update_forums();

    return True;

}

// Deactivate The Plugin
function nivel_actividad_deactivate(){
    //Variables que vamos a utilizar
	global $mybb, $cache, $db;
    // Borrar el grupo de opciones
    $query = $db->simple_select("settinggroups", "gid", "name = \"nivel_actividad\"");
    $rows = $db->fetch_field($query, "gid");
     if($rows){
    //Eliminamos el grupo de opciones
    $db->delete_query("settinggroups", "gid = {$rows}");
    // Borrar las opciones
    $db->delete_query("settings", "gid = {$rows}");
	}

    rebuild_settings();

    //Archivo requerido para reemplazo de templates
	require "../inc/adminfunctions_templates.php";
    //Reemplazos que vamos a hacer en las plantillas 1.- Platilla 2.- Contenido a Reemplazar 3.- Contenido que reemplaza lo anterior
    find_replace_templatesets("postbit", '#'.preg_quote('{$post[\'nivel_actividad\']}').'#', '',0);
    find_replace_templatesets("postbit_classic", '#'.preg_quote('{$post[\'nivel_actividad\']}').'#', '',0);    
    //Se actualiza la info de las plantillas
	$cache->update_forums();

    return True;

}


function nivel_actividad_postbit(&$post)
{
  	global $mybb, $db;
  	
  	if($mybb->settings['nivel_actividad_active'] == '0'){
		return false;
		}
		
  	$post['postnum'] = str_replace($mybb->settings['thousandssep'], '', $post['postnum']);
	$post['reputation'] = str_replace($mybb->settings['thousandssep'], '', $post['reputation']);
	$post['postnum'] = $post['postnum'] + $post['reputation'];
  	
	$daysreg = (time() - $post['regdate']) / (24*3600);
	$postsperday = $post['postnum'] / $daysreg;
	$postsperday = round($postsperday, 2);

	if($postsperday > $post['postnum'])
	{
		$postsperday = $post['postnum'];
	}
	
	// Medir Nivel
	$exp = $post['postnum'];
    if ($exp<=0) {
		$exp = 1; 
	}

    $barraslvl = str_replace(array(' ', ',', '.'), '', $post[postnum]);
    $ppd= round($barraslvl / $exp, 0);
    $level = pow (log10 ($barraslvl), 3);
    $experiencia = floor (100 * ($level - floor ($level)));
    $showlevel = floor ($level + 1);
    $ranmulti =round ($ppd / 6, 1);

    if ($ranmulti > 1.5) { 
		$ranmulti = 1.5; 
		}
		
    if ($ranmulti < 1) { 
		$ranmulti = 1; 
		}

    $ranmax = $level * 25 * $ranmulti;
    $rango= $ppd / 10;
  
    if ($rango >= 1) { 
		$rango= $ranmax; 
		}
    else { 
		$rango= floor ($rango * $ranmax); 
		}

    $rango= floor ($rango);
    $ranmax= floor ($ranmax);

    if ($ranmax <= 0) {
		$zhp = 1; 
		}
    else { 
		$zhp = $ranmax; 
		}
		
     $hpf= floor (100 * ($rango / $zhp)) - 1;
     $actmax= ($exp * $level) / 5;
     $actividad= $barraslvl / 3;
  
     if ($actividad >= $actmax) { 
		 $actividad = $actmax; 
		 }

     $actmax = floor ($actmax);
     $actividad = floor ($actividad);

     if ($actmax <= 0) { 
		 $zmp = 1; 
		 }
     else { 
		 $zmp = $actmax; 
		 }

     $mpf= floor (100 * ($actividad / $zmp)) - 1;

     if ( $hpf >= 98 ) { 
		 $hpf = $hpf - 2; 
		 }
		 
     if ( $ep >= 98 ) { 
		 $ep = $ep - 2; 
		 }
		 
     if ( $mpf >= 98 ) { 
		 $mpf = $mpf - 2; 
		 }

     // Medidor de Ranking por Dark Neo
     $stars = ($level/3)*2;

     // Obtener Nivel
     if($stars >= 20){
		 $stars = 10;
		 }

     if($stars >= 25){
		 $stars = 25;	 
         $starsc = $stars-20;
         $es_ext = "amarilla";
     }
     elseif($stars >= 15){
         $starsc = $stars-15;
         $es_ext = "azul";
     }
     elseif($stars >= 10){
         $starsc = $stars-10;
         $es_ext = "verde";
     }
     elseif($stars >= 5){
         $starsc = $stars-5;
         $es_ext = "roja";
     }
     else{
         $starsc = $stars;
         $es_ext = "gris";
      }
      
     if($starsc < 1){
     $starsc = 1;
     }

     for($iCount=0; $iCount<$starsc; $iCount++){
     $estrellas .= "<img src='images/estrellas/es_{$es_ext}.png' alt='Nivel'>";
     }  

	 $showlevel = my_number_format($showlevel);
	 $rango = my_number_format($rango);
	 $ranmax = my_number_format($ranmax);
	 $actividad = my_number_format($actividad);
	 $actmax = my_number_format($actmax);
	 $exp = $experiencia;
	 $heal = $hpf;
	 $magic = $mpf;
	 
	 if ($experiencia <= 1){$experiencia = 1; $exp = 1;}else if ($experiencia >= 98){$experiencia = 98; $exp = 100;}
	 $experiencia = my_number_format($experiencia);
	 $exp = my_number_format($exp);
	 if ($hpf <= 1){$hpf = 1; $heal=1;}else if ($hpf >= 98){$hpf = 98; $heal = 100;}
	 $heal = my_number_format($heal);
	 $hpf = my_number_format($hpf);
	 if ($mpf <= 1){$mpf = 1; $magic=1;}else if ($mpf >= 98){$mpf = 98; $magic = 100;}
	 $magic = my_number_format($magic);
	 $mpf = my_number_format($mpf);

	 $expf = my_number_format(100 - $experiencia);
	 $magicf = my_number_format(100 - $magic);
	 $healf = my_number_format(100 - $heal);	 
	 
	 $post['nivel_actividad'] = "<div class='postbit_usuario'>Nivel: <span style='font-weight:bold; color:crimson'>{$showlevel} [{$estrellas}]</span><br/>
<span style='font-size:7pt; color:gray'>Puntos Totales: {$actmax}</span><br/>
<span style='font-size:7pt; color:gray'>RANGO {$rango} / {$zhp}<br/>
{$healf}% para subir Nivel</span><br/>
  <table cellspacing='0' cellpadding='0' width='120' border='0' align='center'>
   <tr>
    <td width='114' height='10' class='nopad' style='line-height: 13px;background: url(images/barras/bg_naranja.gif) repeat-x top left;'><img src='images/barras/naranja.gif' width='{$hpf}%' align='left' height='10' alt='Rango' /></td>
   </tr>
  </table>
<span style='font-size:7pt; color:gray'>ACTIVIDAD {$actividad} / {$zmp}<br />
{$magicf}% para subir puntaje </span><br />
  <table cellspacing='0' cellpadding='0' width='120' border='0' align='center'>
   <tr>
    <td width='114' height='10' class='nopad' style='line-height: 13px;background: url(images/barras/bg_verde.gif) repeat-x top left;'><img src='images/barras/verde.gif' width='{$mpf}%' height='10' align='left' alt='Actividad' /></td>
   </tr>
  </table>
<span style='font-size:7pt; color:gray'>EXPERIENCIA {$exp}<br />
{$expf}% para subir actividad </span><br />
  <table cellspacing='0' cellpadding='0' width='120' border='0' align='center'>
   <tr>
    <td width='114' height='10' class='nopad' style='line-height: 13px;background: url(images/barras/bg_azul.gif) repeat-x top left;'><img src='images/barras/azul.gif' width='{$experiencia}%' height='10' align='left' alt='Experiencia' /></td>
   </tr>
  </table>
</div> ";
}
?>
