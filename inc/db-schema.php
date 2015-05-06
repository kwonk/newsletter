<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of newsletter, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2014 Benoit de Marne and contributors
# benoit.de.marne@gmail.com
# Many thanks to Association Dotclear
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!($_s instanceof dbStruct)) { 
	throw new Exception('No valid schema object'); 
}

// ====================================================================================================
// tables
// ====================================================================================================

// newsletter
$_s->newsletter
	->subscriber_id	('integer', 0, true)
	->blog_id		('varchar', 32, false)
	->email			('varchar', 255, false)
	->regcode		('varchar', 255, false)
	->state			('varchar', 255, false)
	->subscribed	('timestamp', 0, false, 'now()')
	->lastsent		('timestamp', 0, true)
	->modesend		('varchar', 10, true)
	
	->primary		('pk_newsletter', 'blog_id', 'subscriber_id')
	->unique		('uk_newsletter', 'subscriber_id')
	;


// ====================================================================================================
// index de référence
// ====================================================================================================


// ====================================================================================================
// index de performance
// ====================================================================================================

$_s->newsletter->index	('idx_newsletter_blog_id', 'btree', 'blog_id');
$_s->newsletter->index	('idx_newsletter_email', 'btree', 'email');
$_s->newsletter->index	('idx_newsletter_lastsent', 'btree', 'lastsent');


// ====================================================================================================
// clées étrangères
// ====================================================================================================

$_s->newsletter->reference	('fk_newsletter_blog', 'blog_id', 'blog', 'blog_id', 'cascade', 'cascade');

?>