<?php

require_once('_functions/functions.php');

require_once('classes/simple_html_dom.php');

error_reporting(E_ALL ^ E_WARNING);
//ini_set('display_errors', TRUE);  
ini_set('error_reporting', E_ALL);

checkrights('pricecrawler', $USERDATA);
//checkrights('user', $USERDATA);

$isbn = str_replace('-', '', $_GET["isbn"]);
$domain = $_GET["domain"];

$price = getPriceFromWeb($isbn, $domain);

print(json_encode($price));

 

function getPriceFromWeb($isbn, $domain) {

	$url = getArticlepageUrl($isbn, $domain);

	if($url == '') { 

		$price = array();
		$price['domain'] = $domain;
		$price['status'] = 'No URL defined';


	} else {

		$price = array();
		$price['url'] = $url;

		switch ($domain) {
		    case 'weltbild.de':
		    case 'weltbild.at':
		    case 'weltbild.ch':
		    case 'hugendubel.de':

				$html = file_get_contents($url);
				$doc = new DOMDocument();
				$doc->loadHTML($html);

	
				$elements = $doc->getElementsByTagName('h1');
				foreach($elements as $i => $element) {

					if($element->hasAttribute('itemprop') && $element->getAttribute('itemprop') == 'name') {
						$price['title'] = (strip_tags(innerHTML($element)));
					}
				}

				unset($elements);


				$elements = $doc->getElementsByTagName('p');
				foreach($elements as $i => $element) {

					if($element->hasAttribute('class') && $element->getAttribute('class') == 'authors') {
						$price['authors'] = (trim(strip_tags(innerHTML($element))));
					}
				}
				unset($elements);


				$elements = $doc->getElementsByTagName('div');
				foreach($elements as $i => $element) {

					if($element->getAttribute('class') == 'detail_image') {

						$images = $element->getElementsByTagName('img');

						foreach($images as $image) {

							if($image->hasAttribute('src')) {
			
								$price['image'] = $image->getAttribute('src');
									
							}
						}


					}
				}
				unset($elements);
	

				$elements = $doc->getElementsByTagName('p');
				foreach($elements as $i => $element) {

					if($element->getAttribute('class') == 'bibliography') {

						$substr = strstr(strip_tags(innerHTML($element)), 'Verlag:');
						$substr = strstr($substr, 'ISBN', true);

						$price['publisher'] = $substr;
					}
				}
				unset($elements);

				$elements = $doc->getElementsByTagName('div');
				foreach($elements as $i => $element) {
			
					if($element->hasAttribute('class') && $element->getAttribute('class') == 'price_box') {

						// Get the currency
						$priceinfos = $element->getElementsByTagName('span');
						foreach($priceinfos as $priceinfo) {
							if($priceinfo->hasAttribute('itemprop') && $priceinfo->getAttribute('itemprop') == 'price') {
								$price['value'] = trim(str_replace(',','.',innerHTML($priceinfo)));
							}
						}

						// Get the currency
						$priceinfos = $element->getElementsByTagName('meta');
						foreach($priceinfos as $priceinfo) {
							if($priceinfo->hasAttribute('itemprop') && $priceinfo->getAttribute('itemprop') == 'priceCurrency') {
			
									$price['currency'] = ($priceinfo->getAttribute('content'));
									if($price['currency'] == '&euro;' OR $price['currency'] == 'EUR' OR ($price['currency']) == '<span>€</span>') {
										$price['currency'] = 'EUR';
									} else if($price['currency'] == 'Fr.') {
										$price['currency'] = 'CHF';
									}
									
							}
						}
						continue;
					}
				}
	
		        break;

		    case 'derclub.de':
		    case 'otto-media.de':
		    case 'donauland.at':
	
				$html = file_get_contents($url);
				$doc = new DOMDocument();
				$doc->loadHTML($html);


				$topelements = $doc->getElementsByTagName('section');
				foreach($topelements as $i => $topelement) {

					if($topelement->hasAttribute('class') && $topelement->getAttribute('class') == 'row product') {

						$elements = $topelement->getElementsByTagName('h1');
						foreach($elements as $i => $element) {
							$price['title'] = (strip_tags(innerHTML($element)));
						}
		
						unset($elements);
		
		
						$elements = $topelement->getElementsByTagName('h3');
						foreach($elements as $i => $element) {
							if($element->getAttribute('class') == 'itemAuthor') {
								$price['authors'] = (strip_tags(innerHTML($element)));
							}
						}
		
						unset($elements);


					}
				}
				unset($topelements);


				$elements = $doc->getElementsByTagName('a');
				foreach($elements as $i => $element) {

					if($element->getAttribute('class') == 'articleDetailGallery mainPreview') {
						$images = $element->getElementsByTagName('img');
						foreach($images as $image) {
							if($image->hasAttribute('src')) {
								$price['image'] = $image->getAttribute('src');
							}
						}


					}
				}
				unset($elements);



				$elements = $doc->getElementsByTagName('div');
				foreach($elements as $i => $element) {

					if($element->hasAttribute('class') && $element->getAttribute('class') == 'price') {
			
	
					// Get the currency
					$priceinfos = $element->getElementsByTagName('p');
						foreach($priceinfos as $priceinfo) {
							if($priceinfo->hasAttribute('class') && $priceinfo->getAttribute('class') == 'priceNormal') {
								$priceparts = explode(' ',strip_tags(innerHTML($priceinfo)));

								foreach($priceparts as $part) {
									if($part == '&euro;') {
										$price['currency'] = 'EUR';
									} else if($part == 'Fr.') {
										$price['currency'] = 'CHF';
									} else {
										$price['value'] = trim(str_replace(',','.',$part));
									}
								} 
							}
						}
					}
				}
				$price['publisher'] = '';
					
	
		        break;

		    case 'thalia.de':
		    case 'thalia.at':
		    case 'thalia.ch':

				$html = file_get_contents($url);

				if(!$html) {
					die('Probleme beim Lesen der URL: '.$url.'');
				}
				$doc = new DOMDocument();
				$doc->loadHTML($html);



				$topelements = $doc->getElementsByTagName('div');
				foreach($topelements as $i => $topelement) {

					if($topelement->hasAttribute('class') && $topelement->getAttribute('class') == 'oProductTitle') {

						$elements = $topelement->getElementsByTagName('h2');
						foreach($elements as $i => $element) {
							$price['title'] = (strip_tags(innerHTML($element)));
						}
		
						unset($elements);
		
		
						$elements = $topelement->getElementsByTagName('h3');
						foreach($elements as $i => $element) {
							if($element->getAttribute('class') == 'itemAuthor') {
								$price['authors'] = (strip_tags(innerHTML($element)));
							}
						}
		
						unset($elements);


					}
				}
				unset($topelements);


				$elements = $doc->getElementsByTagName('a');
				foreach($elements as $i => $element) {

					if($element->getAttribute('class') == 'articleDetailGallery mainPreview') {
						$images = $element->getElementsByTagName('img');
						foreach($images as $image) {
							if($image->hasAttribute('src')) {
								$price['image'] = $image->getAttribute('src');
							}
						}


					}
				}
				unset($elements);



				$elements = $doc->getElementsByTagName('div');
				foreach($elements as $i => $element) {

					if($element->hasAttribute('class') && $element->getAttribute('class') == 'price') {
			
	
					// Get the currency
					$priceinfos = $element->getElementsByTagName('p');
						foreach($priceinfos as $priceinfo) {
							if($priceinfo->hasAttribute('class') && $priceinfo->getAttribute('class') == 'priceNormal') {
								$priceparts = explode(' ',strip_tags(innerHTML($priceinfo)));

								foreach($priceparts as $part) {
									if($part == '&euro;') {
										$price['currency'] = 'EUR';
									} else if($part == 'Fr.') {
										$price['currency'] = 'CHF';
									} else {
										$price['value'] = trim(str_replace(',','.',$part));
									}
								} 
							}
						}
					}
				}
				$price['publisher'] = '';
					
		        break;

		    case 'buch.de':
		    case 'buch.ch':

				$html = file_get_contents($url);

				if(!$html) {
					die('Probleme beim Lesen der URL: '.$url.'');
				}
				$doc = new DOMDocument();
				$doc->loadHTML($html);

				$topelements = $doc->getElementsByTagName('div');
				foreach($topelements as $i => $topelement) {

					if($topelement->hasAttribute('class') && $topelement->getAttribute('class') == 'yui-u') {

						$elements = $topelement->getElementsByTagName('h1');
						foreach($elements as $i => $element) {
							$price['title'] = (strip_tags(innerHTML($element)));
						}
		
						unset($elements);
		
		
						$elements = $topelement->getElementsByTagName('a');
						foreach($elements as $i => $element) {
							if($element->getAttribute('class') == 'b9_author') {
								$price['authors'] = (strip_tags(innerHTML($element)));
							}
						}
		
						unset($elements);


					}
				}
				unset($topelements);


				$elements = $doc->getElementsByTagName('img');
				foreach($elements as $i => $element) {

					if($element->getAttribute('id') == 'previewImage') {
						$price['image'] = $element->getAttribute('src');
					}
				}
				unset($elements);

				$elements = $doc->getElementsByTagName('div');
				foreach($elements as $i => $element) {
			
					if($element->hasAttribute('class') && $element->getAttribute('class') == 'b9sellbox') {
			

						// Get the currency
						$priceinfos = $element->getElementsByTagName('span');
						foreach($priceinfos as $priceinfo) {
							if($priceinfo->hasAttribute('class') && $priceinfo->getAttribute('class') == 'pm_preis b9Value') {
								$price['value'] = trim(str_replace(',','.',innerHTML($priceinfo)));

							}
						}

						// Get the currency
						$priceinfos = $element->getElementsByTagName('span');
						foreach($priceinfos as $priceinfo) {
							if($priceinfo->hasAttribute('class') && $priceinfo->getAttribute('class') == 'pm_waehrung b9Currency') {
								$price['currency'] = innerHTML($priceinfo);
							}
						}

					}
				}
				$price['publisher'] = '';
					
		        break;
		        
		    case 'buecher.de':

				$html = file_get_contents($url);

				if(!$html) {
					$price['value'] = '';
				}
				$doc = new DOMDocument();
				$doc->loadHTML($html);


				$elements = $doc->getElementsByTagName('h1');
				foreach($elements as $i => $element) {

					if($element->hasAttribute('class') && $element->getAttribute('class') == 'titel') {
						$price['title'] = (trim(strip_tags(innerHTML($element))));
					}
				}

				if(!$price['title']) {
					return false;
				}

				unset($elements);


				$elements = $doc->getElementsByTagName('h2');
				foreach($elements as $i => $element) {

					if($element->hasAttribute('class') && $element->getAttribute('class') == 'author') {
						$price['authors'] = (trim(strip_tags(innerHTML($element)),'&nbsp;'));
					}
				}

				unset($elements);


				$elements = $doc->getElementsByTagName('a');
				foreach($elements as $i => $element) {
					if($element->getAttribute('id') == 'zoomlink') {
						$images = $element->getElementsByTagName('img');
						foreach($images as $image) {
							if($image->hasAttribute('src')) {
								$price['image'] = $image->getAttribute('src');
							}
						}
					}
				}
				unset($elements);

				$elements = $doc->getElementsByTagName('div');
				foreach($elements as $i => $element) {

					if($element->hasAttribute('class') && $element->getAttribute('class') == 'price') {
			
						// Get the currency
						$priceinfos = $element->getElementsByTagName('span');
						foreach($priceinfos as $priceinfo) {
							if($priceinfo->hasAttribute('itemprop') && $priceinfo->getAttribute('itemprop') == 'price') {

								$priceparts = explode(' ',strip_tags(innerHTML($priceinfo)));

								foreach($priceparts as $part) {
									if($part == 'EUR') {
										$price['currency'] = 'EUR';
									} else {
										$price['value'] = trim(str_replace(',','.',$part));
									}
								} 
							}
						}
					}
				}
				$price['publisher'] = '';

				
		        break;
	        
		        
		    case 'ebook.de':


				$html = file_get_contents($url);

				if(!$html) {
					die('Probleme beim Lesen der URL: '.$url.'');
				}
				$doc = new DOMDocument();
				$doc->loadHTML($html);


				// Suchergebnis analysieren!
				$elements = $doc->getElementsByTagName('span');
				foreach($elements as $i => $element) {

					if($element->getAttribute('class') == 'times') {

						// Wenn nicht exakt 1 Artikel gefunden wird, dann abbrechen!
						if(innerHTML($element) != 1) {
							return false;
						}

					}
				}
				unset($elements);


				$elements = $doc->getElementsByTagName('h3');
				foreach($elements as $i => $element) {

					if($element->hasAttribute('class') && $element->getAttribute('class') == 'title') {

						$price['title'] =  (trim(strip_tags(innerHTML($element))));
					}
				}

				unset($elements);


				$elements = $doc->getElementsByTagName('div');
				foreach($elements as $i => $element) {

					if($element->hasAttribute('class') && $element->getAttribute('class') == 'author') {

						$price['authors'] =  (trim(strip_tags(innerHTML($element))));
					}
				}

				unset($elements);

				$elements = $doc->getElementsByTagName('div');
				foreach($elements as $i => $element) {

					if($element->getAttribute('class') == 'coverimg') {

						$images = $element->getElementsByTagName('img');

						foreach($images as $image) {

							if($image->hasAttribute('src')) {
			
								$price['image'] = $image->getAttribute('src');
									
							}
						}


					}
				}
				unset($elements);


				$elements = $doc->getElementsByTagName('div');
				foreach($elements as $i => $element) {

					if($element->hasAttribute('class') && $element->getAttribute('class') == 'articlepricebox') {

						// Get the currency
						$priceinfos = $element->getElementsByTagName('div');
						

						foreach($priceinfos as $priceinfo) {
						
							if($priceinfo->hasAttribute('class') && $priceinfo->getAttribute('class') == 'preis') {

								$priceparts = explode(' ',strip_tags(innerHTML($priceinfo)));
								foreach($priceparts as $part) {
									if(trim($part) == '&euro;') {
										$price['currency'] = 'EUR';
									} else {
										$price['value'] = trim(str_replace(',','.',$part));
									}
								} 
							}
						}
					}
				}
				$price['publisher'] = '';

		        break;

		    case 'libreka.de':


				$html = file_get_contents($url);

				if(!$html) {
					die('Probleme beim Lesen der URL: '.$url.'');
				}
				$doc = new DOMDocument();
				$doc->loadHTML($html);




				$elements = $doc->getElementsByTagName('a');
				foreach($elements as $i => $element) {

					if($element->hasAttribute('class') && $element->getAttribute('class') == 'h3') {

						$price['title'] =  (trim(strip_tags(innerHTML($element))));
					}
				}

				unset($elements);


				$elements = $doc->getElementsByTagName('p');
				foreach($elements as $i => $element) {

					if($element->hasAttribute('class') && $element->getAttribute('class') == 'p author') {

						$price['authors'] =  (trim(strip_tags(innerHTML($element))));
					}
				}
				unset($elements);
				
				
				$elements = $doc->getElementsByTagName('p');
				foreach($elements as $i => $element) {

					if($element->hasAttribute('class') && $element->getAttribute('class') == 'p verlaginfo') {

						$price['publisher'] =  (trim(strip_tags(innerHTML($element))));
					}
				}
				unset($elements);



				$elements = $doc->getElementsByTagName('span');
				foreach($elements as $i => $element) {

					if($element->getAttribute('class') == 'span img-frame') {

						$images = $element->getElementsByTagName('img');

						foreach($images as $image) {

							if($image->hasAttribute('src')) {
			
								$price['image'] = 'http://'.$domain.'/'.$image->getAttribute('src');
									
							}
						}


					}
				}
				unset($elements);


				$elements = $doc->getElementsByTagName('div');
				foreach($elements as $i => $element) {

					if($element->hasAttribute('class') && $element->getAttribute('class') == 'price-category') {

						// Get the currency
						$priceinfos = $element->getElementsByTagName('span');
						

						foreach($priceinfos as $priceinfo) {
						
							if(trim(innerHTML($priceinfo)) == 'Epub') {

								foreach($priceinfos as $price_span) {

									if($price_span->getAttribute('class') == 'price price-highlighted') {										
										
										$priceparts = explode('&',strip_tags(innerHTML($price_span)));
										foreach($priceparts as $part) {
											if(trim($part) == 'euro;') {
												$price['currency'] = 'EUR';
											} else {
												$price['value'] = trim(str_replace(',','.',$part));
											}
										} 										
										
									}
								}
								

							}
						}
					}
				}

		        break;
		        
		        
		    case 'amazon.de':

				$html = file_get_contents($url);

				if(!$html) {
					die('Probleme beim Lesen der URL: '.$url.'');
				}
				$doc = new DOMDocument();
				$doc->loadHTML($html);






				$elements = $doc->getElementsByTagName('h3');
				foreach($elements as $i => $element) {

					if($element->hasAttribute('class') && $element->getAttribute('class') == 'newaps') {

						$subelements = $doc->getElementsByTagName('span');
						foreach($subelements as $i => $subelement) {
		
							if($subelement->getAttribute('class') == 'lrg bold') {
		
								$price['title'] =  (trim(strip_tags(innerHTML($subelement))));
							}
							if($subelement->getAttribute('class') == 'med reg') {
		
								$price['authors'] =  (trim(strip_tags(innerHTML($subelement))));
							}
						}
					}
				}

				unset($elements);

				$elements = $doc->getElementsByTagName('img');
				foreach($elements as $i => $element) {

					if($element->getAttribute('class') == 'productImage') {

							if($element->hasAttribute('src')) {
			
								$price['image'] = $element->getAttribute('src');
									
							}

					}
				}
				unset($elements);

				$elements = $doc->getElementsByTagName('li');
				foreach($elements as $i => $element) {

					if($element->hasAttribute('class') && $element->getAttribute('class') == 'newp') {

						// Get the currency
						$priceinfos = $element->getElementsByTagName('span');
						

						foreach($priceinfos as $priceinfo) {
						
							if($priceinfo->hasAttribute('class') && $priceinfo->getAttribute('class') == 'bld lrg red') {

								$priceparts = explode(' ',strip_tags(innerHTML($priceinfo)));

								foreach($priceparts as $part) {
									if($part == 'EUR') {
										$price['currency'] = 'EUR';
									} else {
										$price['value'] = trim(str_replace(',','.',$part));
									}
								} 
							}
						}
					}
				}
				
				$price['publisher'] = '';
								
		        break;
		              				
		}	
	}
	return $price;
}


?>