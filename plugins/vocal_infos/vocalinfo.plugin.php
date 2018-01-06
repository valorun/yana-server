<?php
/*
@name Informations vocales
@author Valentin CARRUESCO <idleman@idleman.fr>
@link http://blog.idleman.fr
@licence CC by nc sa
@version 1.0.0
@description Permet la récuperations d'informations locales ou sur le web comme la météo, les séries TV, l'heure, la date et l'état des GPIO
*/



define('VOCALINFO_COMMAND_FILE','cmd.json');

function vocalinfo_vocal_command(&$response,$actionUrl){
	global $conf;

	$commands = json_decode(file_get_contents(__ROOT__.'/'.Plugin::path().'/'.VOCALINFO_COMMAND_FILE),true);
	foreach($commands as $key=>$command){
		if($command['disabled']=='true') continue;
		$response['commands'][] = array(
		'command'=>$conf->get('VOCAL_ENTITY_NAME').' '.$command['command'],
		'url'=>$actionUrl.$command['url'],
		'confidence'=>($command['confidence']+$conf->get('VOCAL_SENSITIVITY'))
		);
	}


	$response['commands'][] = array(
		'command'=>$conf->get('VOCAL_ENTITY_NAME').' imite le bruit de la poule',
		'callback'=>'vocalinfo_chicken',
		'parameters' => array('un','deux'),
		'confidence'=>0.8);

	$response['commands'][] = array(
		'command'=>$conf->get('VOCAL_ENTITY_NAME').' Définit le mot',
		'callback'=>'vocalinfo_define_word',
		'confidence'=>0.8);

	$response['commands'][] = array(
		'command'=>$conf->get('VOCAL_ENTITY_NAME').' présente toi',
		'callback'=>'vocalinfo_give_me_all',
		'confidence'=>0.8);

	$response['commands'][] = array(
		'command'=>$conf->get('VOCAL_ENTITY_NAME').' enerve toi',
		'callback'=>'vocalinfo_emotion_angry',
		'confidence'=>0.8);

	$response['commands'][] = array(
		'command'=>$conf->get('VOCAL_ENTITY_NAME').' montre toi',
		'callback'=>'vocalinfo_show_you',
		'confidence'=>0.8);

	$response['commands'][] = array(
		'command'=>$conf->get('VOCAL_ENTITY_NAME').' lance le programme',
		'callback'=>'vocalinfo_launch_program',
		'confidence'=>0.8);

	$response['commands'][] = array(
		'command'=>$conf->get('VOCAL_ENTITY_NAME').' test des variables',
		'callback'=>'vocalinfo_test_variables',
		'confidence'=>0.8);
	
}

function vocalinfo_test_variables($text,$confidence,$parameters,$myUser){
	global $conf;
	$cli = new Client();
	$cli->connect();

	$cli->talk("Utilisateur: ".$myUser->getLogin());
	$cli->talk("configuration: ".$conf->get('UPDATE_URL'));
	$cli->disconnect();
}



function vocalinfo_define_word($text,$confidence,$parameters){
	$cli = new Client();
	$cli->connect();
	if($text=='bistro'){
		$cli->talk("Un bistro est un lieu de cultes, ou les sages de ce siècle vont se recueillir");
	}else{
		$json = json_decode(file_get_contents('https://fr.wikipedia.org/w/api.php?action=opensearch&search='.$text),true);
		$define = $json[2][0];
		if($json==false || trim($define)==""){$cli->talk("Le mot ".$text." ne fait pas partie de mot vocabulaire, essayez plutot avec le mot bistro");
			$cli->disconnect();
			return;
		}
		$cli->talk($define);
	}
	$cli->disconnect();
	//Client::execute("D:\Programme_installes\Qt\Tools\QtCreator\bin\qtcreator.exe");
}

function vocalinfo_show_you($text,$confidence,$parameters){
	$cli = new Client();
	$cli->connect();
	$cli->image(YANA_URL."/plugins/vocal_infos/img/yana.jpg");
	$cli->talk("Est ce que tu me trouve jolie?");
	$cli->disconnect();
}

function vocalinfo_emotion_angry($text,$confidence,$parameters){
	$cli = new Client();
	$cli->connect();
	$cli->talk("Tu vois ce qui se passe quand tu me prend la tête ?");
	$cli->emotion("angry");
	$cli->disconnect();
}

function vocalinfo_chicken($text,$confidence,$parameters){
	
	$cli = new Client();
	$cli->connect();
	$cli->sound("C:/poule.wav");
	$cli->disconnect();
}

function vocalinfo_launch_program($text,$confidence,$parameters){
	$cli = new Client();
	$cli->connect();
	switch($text){
		case 'sublime texte':
			$cli->execute("C:\Program Files\Sublime Text 2\sublime_text.exe");
			$cli->talk("Programme en cours de lancement");
		break;
		default:
			$cli->talk("Je ne connais pas le programme : ".$text);
		break;
	}
	
	
	
	$cli->disconnect();
}


function vocalinfo_give_me_all($text,$confidence,$parameters){
	
	$cli = new Client();
	$cli->connect();

	$cli->talk("Je peux parler, evidemment, et t\'écouter plus précisément qu\'avant");
	$cli->talk("Je peux eprouver et montrer des sentiments");
	$cli->talk("Comme la colère");
	$cli->emotion("angry");
	$cli->talk("Ou la timidité");
	$cli->emotion("shy");
	$cli->talk("Et tout un tas d\'autres lubies humaines");
	$cli->talk("Je peux aussi exécuter un programme");
	$cli->execute("D:\Programme_installes\Qt\Tools\QtCreator\bin\qtcreator.exe");
	$cli->talk("ou un son");
	$cli->sound("C:/poule.wav");
	$cli->talk("ou te montrer des images");
	$cli->image("yana.jpg");
	$cli->talk("ou executer une commande domotique");
	//system('gpio write 1 1');
	
	//$cli->talk("ou executer un humain");

	//$cli->talk("non je déconne.");

	$cli->disconnect();
}

function vocalinfo_action(){
	global $_,$conf;

	switch($_['action']){
	
		case 'plugin_vocalinfo_save':
			$commands = json_decode(file_get_contents(Plugin::path().'/'.VOCALINFO_COMMAND_FILE),true);
			
			foreach($_['config'] as $key=>$config){
				$commands[$key]['confidence'] = $config['confidence'];
				$commands[$key]['disabled'] = $config['disabled'];
			}
			file_put_contents(Plugin::path().'/'.VOCALINFO_COMMAND_FILE,json_encode($commands));
			echo 'Enregistré';
		break;
	
		case 'vocalinfo_plugin_setting':
			$conf->put('plugin_vocalinfo_place',$_['weather_place']);
			$conf->put('plugin_vocalinfo_woeid',$_['woeid']);
			header('location:setting.php?section=preference&block=vocalinfo');
		break;

		case 'vocalinfo_sound':
			global $_;
			$response = array('responses'=>array(
										array('type'=>'sound','file'=>$_['sound'])
													)
								);
			$json = json_encode($response);
			echo ($json=='[]'?'{}':$json);
			break;
			
		case 'vocalinfo_devmod':
			$response = array('responses'=>array(
										array('type'=>'command','program'=>'C:\Program Files\Sublime Text 2\sublime_text.exe'),
										array('type'=>'talk','sentence'=>'Sublim text lancé.')
													)
								);


			$json = json_encode($response);
			echo ($json=='[]'?'{}':$json);
		break;

		case 'vocalinfo_gpio_diag':
			$sentence = '';
	
			$gpio = array('actif'=>array(),'inactif'=>array());
			for ($i=0;$i<26;$i++) {
				$commands = array();
				exec("/usr/local/bin/gpio read ".$i,$commands,$return);
				if(trim($commands[0])=="1"){
					$gpio['actif'][] = $i;
				}else{
					$gpio['inactif'][] = $i;
				}
			}
			if(count($gpio['actif'])==0){
				$sentence .= 'Tous les GPIO sont inactifs.';
			}else if(count($gpio['inactif'])==0){
				$sentence .= 'Tous les GPIO sont actifs.';
			}else{
				$sentence .= 'GPIO actifs: '.implode(', ', $gpio['actif']).'. GPIO inactifs: '.implode(', ', $gpio['inactif']).'.';
			}
			

			$response = array('responses'=>array(
										array('type'=>'talk','sentence'=>$sentence)
													)
								);


			$json = json_encode($response);
			echo ($json=='[]'?'{}':$json);
		break;
		case 'vocalinfo_commands':

			
			$actionUrl = 'http://'.$_SERVER['SERVER_ADDR'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
			$actionUrl = substr($actionUrl,0,strpos($actionUrl , '?'));
			$commands = array();
			Plugin::callHook("vocal_command", array(&$commands,$actionUrl));
			$sentence ='Je répond aux commandes suivantes: ';
			foreach ($commands['commands'] as $command) {
				$sentence .=$command['command'].'. ';
			}

			$response = array('responses'=>array(
										array('type'=>'talk','sentence'=>$sentence)
													)
								);

			$json = json_encode($response);
			echo ($json=='[]'?'{}':$json);
		break;
			//MAJ pour la nouvelle API
		case 'vocalinfo_meteo':
                        global $_;
                                if($conf->get('plugin_vocalinfo_woeid')!=''){
                                $BASE_URL = "http://query.yahooapis.com/v1/public/yql";
                                if((isset($_['today'])))
                                {
                                        $yql_query = 'select item.condition from weather.forecast where woeid='.$conf->get('plugin_vocalinfo_woeid').' and u=\'c\'';
                                }
                                else
                                {
                                        $yql_query = 'select item.forecast from weather.forecast where woeid='.$conf->get('plugin_vocalinfo_woeid').' and u=\'c\'';
                                }
                                $yql_query_url = $BASE_URL . "?q=" . urlencode($yql_query) . "&format=json";

                                $result =file_get_contents($yql_query_url);
                                $json=json_decode($result);

                                if((isset($_['today'])))
                                {
                                        $weekdays=$json->{'query'}->{'results'}->{'channel'}->{'item'};
                                }
                                else
                                {
                                        $weekdays=$json->{'query'}->{'results'}->{'channel'};
                                }
                                //Codes disponibles ici: http://developer.yahoo.com/weather/#codes
                                $textTranslate = array(
                                                                                '1' => 'Attention: Tornade!',
                                                                                '2' => 'Attention: Ouragan!',
                                                                                '3' => 'Tempètes violentes',
                                                                                '4' => 'Tempêtes',
                                                                                '5' => 'Pluie et neiges',
                                                                                '6' => 'Pluie et neige fondue',
                                                                                '7' => 'Neige et neige fondue',
                                                                                '8' => 'Bruine verglassant',
                                                                                '9' => 'Bruine',
                                                                                '10' => 'Pluie verglassant',
																				'11' => 'des averses',
                                                                                '12' => 'Averse',
                                                                                '13' => 'Bourrasque de neige',
                                                                                '14' => 'Averse de neige lègére',
                                                                                '15' => 'Chasse neige',
                                                                                '16' => 'Neige',
                                                                                '17' => 'Grêle',
                                                                                '18' => 'Neige fondue',
                                                                                '19' => 'Poussière',
                                                                                '20' => 'Brouillard',
                                                                                '21' => 'Brume',
                                                                                '22' => 'Fumée',
                                                                                '23' => 'Froid et venteux',
                                                                                '24' => 'Venteux',
                                                                                '25' => 'Froid',
                                                                                '26' => 'Nuageux',
                                                                                '27' => 'plutot Nuageux',
                                                                                '28' => 'plutot Nuageux',
                                                                                '29' => 'Partiellement nuageux',
                                                                                '30' => 'Partiellement nuageux',
                                                                                '31' => 'Temps clair',
                                                                                '32' => 'ensoleillé',
                                                                                '33' => 'Ciel dégagé',
                                                                                '34' => 'Ciel dégagé',
                                                                                '35' => 'Pluie et grêle',
                                                                                '36' => 'Chaud',
                                                                                '37' => 'Orages isolées',
                                                                                '38' => 'Orages épars',
                                                                                '39' => 'Orages épars',
                                                                                '40' => 'Averses éparses',
																				'41' => 'Fortes chutes de neige',
                                                                                '42' => 'Averse de neige éparse',
                                                                                '43' => 'Fortes neiges',
                                                                                '44' => 'Partiellement nuageux avec du vent',
                                                                                '45' => 'Orages',
                                                                                '46' => 'Tempêtes de neige',
                                                                                '47' => 'Grain sous orage isolées',
                                                                                '3200' => 'Non disponible'
                                                                                );
                                $dayTranslate = array('Wed'=>'mercredi',
                                                                                'Sat'=>'samedi',
                                                                                'Mon'=>'lundi',
                                                                                'Tue'=>'mardi',
                                                                                'Thu'=>'jeudi',
                                                                                'Fri'=>'vendredi',
                                                                                'Sun'=>'dimanche');
                                $affirmation = '';

                                foreach($weekdays as $day){
                                        if(!(isset($_['today']))){
                                                $day=$day->{'item'}->{'forecast'};
                                        }
                                        if (substr($day->{'date'},-6,2) == "AM")
                                        {
                                                $sub_condition = $day->{'code'};
                                                $condition = (isset($textTranslate[''.$sub_condition])?$textTranslate[''.$sub_condition]:$sub_condition)." dans la matinée";
                                        }
										elseif (substr($day->{'date'},-6,2) == "PM") {
                                                $sub_condition = $day->{'code'};
                                                $condition = (isset($textTranslate[''.$sub_condition])?$textTranslate[''.$sub_condition]:$sub_condition)." dans l'après midi";
                                         }
                                         elseif (substr($day->{'date'},-6,4) == "Late") {
                                                $sub_condition = $day->{'code'};
                                                $condition = (isset($textTranslate[''.$sub_condition])?$textTranslate[''.$sub_condition]:$sub_condition)." en fin de journée";
                                         }
                                         else
                                         {
                                                $condition = isset($textTranslate[''.$day->{'code'}])?$textTranslate[''.$day->{'code'}]:$day->{'code'};
                                         }


                                        if((isset($_['today'])))
                                        {
                                                $affirmation .= 'Aujourd\'hui '.$day->{'temp'}.' degrés, '.$condition.', ';
                                        }
                                        else
                                        {
                                                $affirmation .= $dayTranslate[''.substr($day->{'day'},0,3)].' de '.$day->{'low'}.' à '.$day->{'high'}.' degrés, '.$condition.', ';
                                        }
                                }
                        }else{
                                $affirmation = 'Vous devez renseigner votre ville dans les préférences de l\'interface oueb, je ne peux rien vous dire pour le moment.';
                        }

                                $response = array('responses'=>array(
                                                                                array('type'=>'talk','sentence'=>$affirmation)
                                                                                                        )
                                                                );
                                $json = json_encode($response);
                                echo ($json=='[]'?'{}':$json);


		break;

		case 'vocalinfo_tv':
			global $_;

				libxml_use_internal_errors(true);

			
				
				$contents = file_get_contents('http://webnext.fr/epg_cache/programme-tv-rss_'.date('Y-m-d').'.xml');
				

				$xml = simplexml_load_string($contents);
				$emissions = $xml->xpath('/rss/channel/item');

				$focus = array();
				
				
				$time = time();
				$date = date('m/d/Y ',$time);
				$focusedCanals = array('TF1','France 2','France 3','France 4','Canal+','Arte','France 5','M6');
				foreach($emissions as $emission){
					$item = array();
					list($item['canal'],$item['hour'],$item['title']) = explode(' | ',$emission->title);
					$itemTime = strtotime($date.$item['hour']);
					if($itemTime>=$time-3600 && $itemTime<=$time+3600 && in_array($item['canal'], $focusedCanals)){
						if(	(isset($_['category']) && $_['category']==''.$emission->category) || !isset($_['category']) ){
							$item['category'] = ''.$emission->category;
							$item['description'] = strip_tags(''.$emission->description);
							$focus[$item['title'].$item['canal']][] = $item;
						}
					}
				}
			
				$affirmation = '';
				$response = array();

				foreach($focus as $emission){
						$nb = count($emission);
						$emission = $emission[0];
						$affirmation = array();
						$affirmation['type'] = 'talk';
						//$affirmation['style'] = 'slow';
						$affirmation['sentence'] = ($nb>1?$nb.' ':'').ucfirst($emission['category']).', '.$emission['title'].' à '.$emission['hour'].' sur '.$emission['canal'];
						$response['responses'][] = $affirmation;
				}
				
				$json = json_encode($response);
				echo ($json=='[]'?'{}':$json);


		break;
		case 'vocalinfo_hour':
			global $_;
				$affirmation = 'Il est '.date('H:i');
				$response = array('responses'=>array(
										array('type'=>'talk','sentence'=>$affirmation)
													)
								);
				$json = json_encode($response);
				echo ($json=='[]'?'{}':$json);
		break;
		case 'vocalinfo_day':
			global $_;
				$affirmation = 'Nous sommes le '.date('d/m/Y');
				$response = array('responses'=>array(
										array('type'=>'talk','sentence'=>$affirmation)
													)
								);
				$json = json_encode($response);
				echo ($json=='[]'?'{}':$json);
		break;
		case 'vocalinfo_wikipedia':
			global $_;
			
				$url = 'http://fr.wikipedia.org/w/api.php?action=parse&page='.$_['word'].'&format=json&prop=text&section=0';
				$ch = curl_init($url);
				curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; fr-FR; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" ); // required by wikipedia.org server; use YOUR user agent with YOUR contact information. (otherwise your IP might get blocked)
				$c = curl_exec($ch);

				$json = json_decode($c);

				$content = $json->{'parse'}->{'text'}->{'*'}; // get the main text content of the query (it's parsed HTML)

				$affirmation = strip_tags(str_replace('&#160;', ' ', $content)); // '&#160;' is a space, but is not recognized by yana trying to read "160"
				
				$response = array('responses'=>array(
									array('type'=>'talk','sentence'=>$affirmation)
												)
								);
				$json = json_encode($response);
				echo ($json=='[]'?'{}':$json);
		break;
		case 'vocalinfo_mood':
			global $_;
				$possible_answers = array(
					'parfaitement'
					,'ça pourrait aller mieux'
					,'ça roule mon pote !'
					,'nickel'
					,'pourquoi cette question ?'
				);
				
				$affirmation = $possible_answers[rand(0,count($possible_answers)-1)];
				$response = array('responses'=>array(
										array('type'=>'talk','sentence'=>$affirmation)
													)
								);
				$json = json_encode($response);
				echo ($json=='[]'?'{}':$json);
		break;
	}

}

function vocalinfo_event(&$response){
	
	if(date('d/m H:i')=='24/01 15:00'){
		if(date('s')<45){
		$response['responses']= array(
								array('type'=>'sound','file'=>'sifflement.wav'),
								array('type'=>'talk','sentence'=>'C\'est l\'anniversaire de mon créateur, pensez à lui offrir une bière!')
							);
		}
	}
}

function vocalinfo_plugin_preference_menu(){
	global $_;
	echo '<li '.(@$_['block']=='vocalinfo'?'class="active"':'').'><a  href="setting.php?section=preference&block=vocalinfo"><i class="fa fa-angle-right"></i> Informations Vocales</a></li>';
}
function vocalinfo_plugin_preference_page(){
	global $myUser,$_,$conf;
	if((isset($_['section']) && $_['section']=='preference' && @$_['block']=='vocalinfo' )  ){
		if($myUser!=false){
	Plugin::addjs("/js/woeid.js",true);
	Plugin::addJs('/js/main.js',true);
	$commands = json_decode(file_get_contents(Plugin::path().'/'.VOCALINFO_COMMAND_FILE),true);

	?>

		<div class="span9 userBloc">
		<legend>Commandes</legend>
	<table class="table table-striped table-bordered">
		<tr>
			<th></th>
			<th>Commande</th>
			<th>Confidence</th>
		</tr>
	<?php	foreach($commands as $key=>$command){ ?>
			<tr class="command" data-id="<?php echo $key; ?>"><td><input type="checkbox" <?php echo $command['disabled']=='true'?'':'checked="checked"' ?> class="enabled"></td><td><?php echo $conf->get('VOCAL_ENTITY_NAME').' '.$command['command']; ?></td><td><input type="text" class="confidence" value="<?php echo $command['confidence']; ?>"/></td></tr>
	<?php	}  ?>
		<tr>
			<td colspan="3"><div class="btn" onclick="plugin_vocalinfo_save();">Enregistrer</div></td>
		</tr>
	</table>
		
		
			<form class="form-inline" action="action.php?action=vocalinfo_plugin_setting" method="POST">
			<legend>Météo</legend>
			    <label>Tapez le nom de votre ville et votre pays</label>
			    <input type="text" class="input-xlarge" name="weather_place" value="<?php echo $conf->get('plugin_vocalinfo_place');?>" placeholder="Votre ville">	
			    <span id="weather_query" class="btn">Chercher</span>
			    <br/><br/><label>Votre Identifiant WOEID</label>
			    <input type="text" class="input-large" name="woeid" value="<?php echo $conf->get('plugin_vocalinfo_woeid');?>" placeholder="Votre WOEID">					
			    <button type="submit" class="btn">Sauvegarder</button>
	    </form>
		</div>

<?php }else{ ?>

		<div id="main" class="wrapper clearfix">
			<article>
					<h3>Vous devez être connecté</h3>
			</article>
		</div>
<?php

		}
	}
}





Plugin::addHook("preference_menu", "vocalinfo_plugin_preference_menu"); 
Plugin::addHook("preference_content", "vocalinfo_plugin_preference_page"); 


Plugin::addHook("get_event", "vocalinfo_event");    
Plugin::addHook("action_post_case", "vocalinfo_action");    
Plugin::addHook("vocal_command", "vocalinfo_vocal_command");
?>
