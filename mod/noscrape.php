<?php

function noscrape_init(&$a) {

	if($a->argc > 1)
		$which = $a->argv[1];
	else
		killme();

	$profile = 0;
	if((local_user()) && ($a->argc > 2) && ($a->argv[2] === 'view')) {
		$which = $a->user['nickname'];
		$profile = $a->argv[1];
	}

	profile_load($a,$which,$profile);

	if(!$a->profile['net-publish'])
		killme();

	$keywords = ((x($a->profile,'pub_keywords')) ? $a->profile['pub_keywords'] : '');
	$keywords = str_replace(array('#',',',' ',',,'),array('',' ',',',','),$keywords);
	$keywords = explode(',', $keywords);

	$json_info = array(
		'fn' => $a->profile['name'],
		'key' => $a->profile['pubkey'],
		'homepage' => $a->get_baseurl()."/profile/{$which}",
		'comm' => (x($a->profile,'page-flags')) && ($a->profile['page-flags'] == PAGE_COMMUNITY),
		'photo' => $a->profile['photo'],
		'tags' => $keywords
	);

	if(is_array($a->profile) AND !$a->profile['hide-friends']) {
		$r = q("SELECT `gcontact`.`updated` FROM `contact` INNER JOIN `gcontact` WHERE `gcontact`.`nurl` = `contact`.`nurl` AND `self` AND `uid` = %d LIMIT 1",
			intval($a->profile['uid']));
		if(count($r))
			$json_info["updated"] =  date("c", strtotime($r[0]['updated']));

		$r = q("SELECT COUNT(*) AS `total` FROM `contact` WHERE `uid` = %d AND `self` = 0 AND `blocked` = 0 and `pending` = 0 AND `hidden` = 0 AND `archive` = 0
				AND `network` IN ('%s', '%s', '%s', '')",
			intval($a->profile['uid']),
			dbesc(NETWORK_DFRN),
			dbesc(NETWORK_DIASPORA),
			dbesc(NETWORK_OSTATUS)
		);
		if(count($r))
			$json_info["contacts"] = intval($r[0]['total']);
	}

	//These are optional fields.
	$profile_fields = array('pdesc', 'locality', 'region', 'postal-code', 'country-name', 'gender', 'marital', 'about');
	foreach($profile_fields as $field)
		if(!empty($a->profile[$field])) $json_info["$field"] = $a->profile[$field];

	$dfrn_pages = array('request', 'confirm', 'notify', 'poll');
	foreach($dfrn_pages as $dfrn)
		$json_info["dfrn-{$dfrn}"] = $a->get_baseurl()."/dfrn_{$dfrn}/{$which}";

	//Output all the JSON!
	header('Content-type: application/json; charset=utf-8');
	echo json_encode($json_info);
	exit;

}
