<?php
// This file is generated. Do not modify it manually.
return array(
	'lo-detail' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'frs/lo-detail',
		'version' => '1.0.0',
		'title' => 'Loan Officer Profile',
		'category' => 'widgets',
		'icon' => 'businessperson',
		'description' => 'Display a full loan officer profile page.',
		'keywords' => array(
			'profile',
			'loan officer',
			'mortgage',
			'detail'
		),
		'textdomain' => 'frs-profile-directory',
		'supports' => array(
			'interactivity' => true,
			'align' => array(
				'wide',
				'full'
			),
			'html' => false
		),
		'attributes' => array(
			'hubUrl' => array(
				'type' => 'string',
				'default' => ''
			),
			'slug' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'editorScript' => 'file:./index.js',
		'viewScriptModule' => 'file:./view.js',
		'style' => 'file:./style.css',
		'render' => 'file:./render.php'
	),
	'lo-directory' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'frs/lo-directory',
		'version' => '1.0.0',
		'title' => 'Loan Officer Directory',
		'category' => 'widgets',
		'icon' => 'businessperson',
		'description' => 'Display a searchable directory of loan officers with service area filtering.',
		'keywords' => array(
			'directory',
			'loan officer',
			'team',
			'mortgage'
		),
		'textdomain' => 'frs-profile-directory',
		'supports' => array(
			'align' => array(
				'wide',
				'full'
			),
			'html' => false
		),
		'attributes' => array(
			'hubUrl' => array(
				'type' => 'string',
				'default' => ''
			),
			'showSearch' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showServiceAreaFilter' => array(
				'type' => 'boolean',
				'default' => true
			),
			'perPage' => array(
				'type' => 'number',
				'default' => 12
			),
			'columns' => array(
				'type' => 'number',
				'default' => 4
			)
		),
		'editorScript' => 'file:./index.js',
		'viewScript' => 'file:./view.js',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	)
);
