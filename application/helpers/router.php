<?php

return array(
	'/' => array(
		'controller' => 'application',
		'action' => 'start',
		'params' => array(
			'language' => 'hu'
		)
	),
	'/ajax/halloffame' => array(
		'controller' => 'ajax',
		'action' => 'halloffame'
	)
);