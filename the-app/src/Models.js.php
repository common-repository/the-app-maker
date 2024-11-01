<?php if (!defined('APP_IS_VALID')) die('// Move along...'); ?>

<?php
	if (is_array($the_app->get('models'))){
		foreach ($the_app->get('models') as $key => $model){
			echo "Ext.regModel('$key', {\n";
			$sep = "\t";
			foreach ($model as $what => $details){
				echo $sep."$what: ";
				echo TheAppMaker::anti_escape(json_encode($details));
				echo "\n";
				$sep = "\t,";
			}
			echo "});\n";
		}
	}
?>
