<?php
/**
 * @author Vasile Giorgi
 *
 * This file contains all available languages
 * This is a work in progress if a certain language is not find here fill free to add it based on following logic:
 * 	id = ISO 639-1 language code,
 *  a3 = ISO 639-2 three-letter codes, for the same languages as 639-1
 *  regional =
 *  name = english name of the language
 *  label = regional specific language name
 *
 *  @link http://www.loc.gov/standards/iso639-2/php/English_list.php
 *  @link http://www.iso.org/iso/catalogue_detail?csnumber=39534
 **/
a::$arLangs = array(
	'bg' => array('a3' =>'bul', 'regional' => '', 'name' => 'Bulgarian', 'label' => 'български'),
	'cs' => array('a3' =>'ces', 'regional' => '', 'name' => 'Czech', 'label' => 'čeština'),
	'da' => array('a3' =>'dan', 'regional' => '', 'name' => 'Danish', 'label' => 'Dansk'),
	'de' => array('a3' =>'deu', 'regional' => 'de-DE', 'name' => 'German', 'label' => 'Deutsch'),
	'en' => array('a3' =>'eng', 'regional' => 'en-US', 'name' => 'English', 'label' => 'English'),
	'es' => array('a3' =>'spa', 'regional' => '', 'name' => 'Spanish / Castilian', 'label' => 'español / castellano'),
	'et' => array('a3' =>'est', 'regional' => '', 'name' => 'Estonian', 'label' => 'eesti'),
	'fi' => array('a3' =>'fin', 'regional' => '', 'name' => 'Finnish', 'label' => 'suomi'),
	'fr' => array('a3' =>'fra', 'regional' => '', 'name' => 'French', 'label' => 'français'),
	'hr' => array('a3' =>'hrv', 'regional' => '', 'name' => 'Croatian', 'label' => 'Hrvatski'),
	'hu' => array('a3' =>'hun', 'regional' => '', 'name' => 'Hungarian', 'label' => 'Magyar'),
	'is' => array('a3' =>'isl', 'regional' => '', 'name' => 'Icelandic', 'label' => 'Íslenska'),
	'it' => array('a3' =>'ita', 'regional' => '', 'name' => 'Italian', 'label' => 'Italiano'),
	'lt' => array('a3' =>'lit', 'regional' => '', 'name' => 'Lithuanian', 'label' => 'Lietuvių'),
	'lv' => array('a3' =>'lav', 'regional' => '', 'name' => 'Latvian', 'label' => 'Latviešu'),
	'mt' => array('a3' =>'mlt', 'regional' => '', 'name' => 'Maltese', 'label' => 'Malti'),
	'nl' => array('a3' =>'nld', 'regional' => '', 'name' => 'Dutch', 'label' => 'Nederlands'),
	'no' => array('a3' =>'nor', 'regional' => '', 'name' => 'Norwegian', 'label' => 'Norsk'),
	'pl' => array('a3' =>'pol', 'regional' => '', 'name' => 'Polish', 'label' => 'polski'),
	'pt' => array('a3' =>'por', 'regional' => '', 'name' => 'Portuguese', 'label' => 'Português'),
	'ro' => array('a3' =>'ron', 'regional' => 'ro-RO', 'name' => 'Romanian', 'label' => 'Română'),
	'sk' => array('a3' =>'slk', 'regional' => '', 'name' => 'Slovak', 'label' => 'slovenčina'),
	'sl' => array('a3' =>'slv', 'regional' => '', 'name' => 'Slovenian', 'label' => 'slovenščina'),
	'sv' => array('a3' =>'swe', 'regional' => '', 'name' => 'Swedish', 'label' => 'Svenska'),
	'tr' => array('a3' =>'tur', 'regional' => '', 'name' => 'Turkish', 'label' => 'Türkçe'),
);
?>