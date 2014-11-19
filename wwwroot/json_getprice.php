<?php

require_once('_functions/functions.php');

require_once('classes/simple_html_dom.php');

//checkrights('pricecrawler', $USERDATA);
checkrights('user', $USERDATA);

$isbn = str_replace('-', '', $_GET["isbn"]);
$domain = $_GET["domain"];

$price = getPriceFromWeb($isbn, $domain);

print(json_encode($price));

$retry = 0;

function getPriceFromWeb($isbn, $domain) {

	global $retry;
	
	$url = getArticlepageUrl($isbn, $domain);

	if($url == '') { 

		$price = array();
		$price['domain'] = $domain;
		$price['status'] = 'No URL defined';


	} else {

		$price = array();
		$price['url'] = $url;

		switch ($domain) {
		
		    case 'langenscheidt.de':


				$html = file_get_contents($url);
				$doc = new DOMDocument();
				$doc->loadHTML($html);


				$topelements = $doc->getElementsByTagName('div');
				foreach($topelements as $i => $topelement) {

					if($topelement->hasAttribute('class') && $topelement->getAttribute('class') == 'titel-container') {

						$elements = $topelement->getElementsByTagName('h2');
		
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'widget-title') {
							
								if(substr(trim(strip_tags(innerHTML($element))),0,16) == 'Wir haben leider') {
		
									return false;
		
								}
		
							}
						}
						unset($elements);
						
						
						$elements = $topelement->getElementsByTagName('h1');
		
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'main-title') {
								$price['title'] = innerHTML($element);
							}
						}
						unset($elements);
						
						
						$elements = $topelement->getElementsByTagName('h3');
		
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'article-title') {
								$price['subtitle'] = innerHTML($element);
							}
						}
						unset($elements);
						
						
						$elements = $topelement->getElementsByTagName('div');
		
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'artikel-preis') {
							
								// Get the currency
								$priceinfos = $element->getElementsByTagName('div');
								foreach($priceinfos as $priceinfo) {
									if($priceinfo->hasAttribute('class') && $priceinfo->getAttribute('class') == 'preis') {
										$price['value'] = trim(str_replace(',','.', trim(str_replace('&euro;', '', innerHTML($priceinfo)))));
										$price['currency'] = 'EUR';
				
									}
								}							
							
							}
						}
						unset($elements);
		
		
						$elements = $topelement->getElementsByTagName('span');
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'cover') {
							
								$images = $element->getElementsByTagName('img');
								foreach($images as $i => $image) {
				
									if($image->getAttribute('class') == 'shadow margin') {
				
										$price['image'] = 'http://'.$domain.'/'.$image->getAttribute('src');
						
									}
								}							
							
							}
						}
						unset($elements);	

		
						$elements = $topelement->getElementsByTagName('div');
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'tab-artikel') {
							
								$subelements = $element->getElementsByTagName('div');
								foreach($subelements as $i => $subelement) {
				
									if($subelement->getAttribute('class') == 'text-block produktkurztextonline') {
				
										$price['text'] = trim(strip_tags(innerHTML($subelement)));
						
									}
								}							
							
							}
						}
						unset($elements);			
		
		

					}
				}
	
		        break;

		    case '2_langenscheidt.de':


				$html = file_get_contents($url);
				$doc = new DOMDocument();
				$doc->loadHTML($html);


				$topelements = $doc->getElementsByTagName('div');
				foreach($topelements as $i => $topelement) {

					if($topelement->hasAttribute('class') && $topelement->getAttribute('class') == 'content-container') {

						$elements = $topelement->getElementsByTagName('h2');
		
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'widget-title') {
							
								if(substr(trim(strip_tags(innerHTML($element))),0,16) == 'Wir haben leider') {
		
									return false;
		
								}
		
							}
						}
						unset($elements);
						
						
						$elements = $topelement->getElementsByTagName('div');
		
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'titel') {
		
								$titletags = $element->getElementsByTagName('h2');
		
								foreach($titletags as $h2) {
			
									$anchors = $h2->getElementsByTagName('a');
			
									foreach($anchors as $anchor) {
			
										if($anchor->hasAttribute('title')) {
						
											//$price['title'] = $anchor->getAttribute('title');
												
										}
									}
								}
							}
						}
						unset($elements);
		
		
						// Get the currency
						$priceinfos = $topelement->getElementsByTagName('div');
						foreach($priceinfos as $priceinfo) {
							if($priceinfo->hasAttribute('class') && $priceinfo->getAttribute('class') == 'preis') {
								$price['value'] = trim(str_replace(',','.', trim(str_replace('&euro;', '', innerHTML($priceinfo)))));
								$price['currency'] = 'EUR';
		
							}
						}
		
		
		
		
						$images = $topelement->getElementsByTagName('img');
						foreach($images as $i => $image) {
		
							if($image->getAttribute('class') == 'shadow') {
		
								$price['image'] = 'http://'.$domain.'/'.$image->getAttribute('src');
				
							}
						}
					}
				}
	
		        break;


		    case 'buchkatalog.de':
	
				$html = file_get_contents($url);
				$doc = new DOMDocument();
				$doc->loadHTML($html);
				
				$topelements = $doc->getElementsByTagName('form');
				foreach($topelements as $i => $topelement) {

					if($topelement->hasAttribute('name') && $topelement->getAttribute('name') == 'Detailanzeige') {


						$elements = $topelement->getElementsByTagName('span');
						foreach($elements as $i => $element) {
							if($element->getAttribute('class') == 'btitel') {
								$price['title'] = (strip_tags(innerHTML($element)));
							}
						}
		
						unset($elements);

						$elements = $doc->getElementsByTagName('img');
						foreach($elements as $i => $element) {
		
							if($element->getAttribute('width') == '132') {

					
										$price['image'] = $element->getAttribute('src');
											
							}
						}
						unset($elements);

						$elements = $topelement->getElementsByTagName('table');
		
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'pricebox') {
		
								$subelements = $element->getElementsByTagName('span');
		
								foreach($subelements as $subelement) {
			
									if($subelement->hasAttribute('class') && $subelement->getAttribute('class') == 'title4') {
									
										$price['value'] = innerHTML($subelement);
												
									}
								}
							}
						}
						unset($elements);

					}
				}
				
				unset($topelements);
	
		        break;

		    case 'weltbild.de':
		    case 'weltbild.at':
		    case 'weltbild.ch':

				$html = file_get_contents($url);
				$doc = new DOMDocument();
				$doc->loadHTML($html);


				$topelements = $doc->getElementsByTagName('div');
				foreach($topelements as $i => $topelement) {

					if($topelement->hasAttribute('class') && $topelement->getAttribute('class') == 'overview') {

						$elements = $doc->getElementsByTagName('h1');
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('itemprop') && $element->getAttribute('itemprop') == 'name') {
								$price['title'] = (strip_tags(innerHTML($element)));
							}
						}
		
						unset($elements);
		
	
						$elements = $doc->getElementsByTagName('p');
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'subtitle') {
								$price['subtitle'] = (strip_tags(innerHTML($element)));
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
			
						$elements = $topelement->getElementsByTagName('div');
					

						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'adtext') {
								$price['text'] = strip_tags(innerHTML($element));
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
					}
				}

				if(!$price['subtitle']) {
					$price['subtitle'] = '<span class="error" style="color:red;">Fehler: Kein Untertitel gefunden</span>';
					$price['seostatus'] = 'notice';
				}
				
		        break;

		    case 'derclub.de':
		    
				$html = file_get_contents($url);
				$doc = new DOMDocument();
				$doc->loadHTML($html);


				$topelements = $doc->getElementsByTagName('main');
				foreach($topelements as $i => $topelement) {

					if($topelement->hasAttribute('role') && $topelement->getAttribute('role') == 'main') {

						$elements = $doc->getElementsByTagName('h1');
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('itemprop') && $element->getAttribute('itemprop') == 'name') {
								$price['title'] = (strip_tags(innerHTML($element)));
							}
						}
		
						unset($elements);

						$elements = $doc->getElementsByTagName('p');
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'sub-text') {
								$price['subtitle'] = (strip_tags(innerHTML($element)));
							}
						}
		
						unset($elements);		
		
						$elements = $doc->getElementsByTagName('div');
						foreach($elements as $i => $element) {
		
							if($element->getAttribute('class') == 'img grid3') {
		
								$images = $element->getElementsByTagName('img');
		
								foreach($images as $image) {
		
									if($image->hasAttribute('src')) {
					
										$price['image'] = 'http://'.$domain.'/'.$image->getAttribute('src');
											
									}
								}
		
		
							}
						}
						unset($elements);
			
						$elements = $topelement->getElementsByTagName('p');
					

						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'summary') {
								$price['text'] = strip_tags(innerHTML($element));
							}
						}
						unset($elements);

		
						$elements = $doc->getElementsByTagName('article');
						foreach($elements as $i => $element) {
					
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'pricePresentation') {
		
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
					}
				}

				if(!$price['subtitle']) {
					$price['subtitle'] = '<span class="error" style="color:red;">Fehler: Kein Untertitel gefunden</span>';
					$price['seostatus'] = 'notice';
				}

		    	break;
		    	
		    case 'otto-media.de':
		    case 'donauland.at':

				$html = file_get_contents($url);
				$doc = new DOMDocument();
				$doc->loadHTML($html);

				$elements = $doc->getElementsByTagName('h1');

				foreach($elements as $i => $element) {

						$subelements = $element->getElementsByTagName('span');

						foreach($subelements as $subelement) {

							if($subelement->hasAttribute('itemprop') && $subelement->getAttribute('itemprop') == 'name') {
			
								$price['title'] = trim(strip_tags(innerHTML($subelement)));
									
							}
						}
				}


				unset($subelements);


				foreach($elements as $i => $element) {

						$subelements = $element->getElementsByTagName('span');

						foreach($subelements as $subelement) {

							if($subelement->hasAttribute('class') && $subelement->getAttribute('class') == 'author') {
			
								$price['authors'] = trim(strip_tags(innerHTML($subelement)));
									
							}
						}
				}

				unset($subelements);
				unset($elements);


				$elements = $doc->getElementsByTagName('div');
				foreach($elements as $i => $element) {

					if($element->getAttribute('class') == 'image') {

						$images = $element->getElementsByTagName('img');

						foreach($images as $image) {

							if($image->hasAttribute('id') == 'imagemax' && $image->hasAttribute('src')) {
			
								$price['image'] = 'http://www.'.$domain.'/'.$image->getAttribute('src');
									
							}
						}


					}
				}
				unset($elements);
	
				$elements = $doc->getElementsByTagName('div');
				foreach($elements as $i => $element) {
			
					if($element->hasAttribute('class') && $element->getAttribute('class') == 'normaldiv') {
			

	
						// Get the currency
						$priceinfos = $element->getElementsByTagName('span');
						foreach($priceinfos as $priceinfo) {
							if($priceinfo->hasAttribute('itemprop') && $priceinfo->getAttribute('itemprop') == 'price') {
								$price['value'] = trim(str_replace(',','.', trim(str_replace('&euro;', '', innerHTML($priceinfo)))));
							}
						}

						// Get the currency
						$priceinfos = $element->getElementsByTagName('meta');
						foreach($priceinfos as $priceinfo) {
							if($priceinfo->hasAttribute('itemprop') && $priceinfo->getAttribute('itemprop') == 'priceCurrency') {
								$price['currency'] = $priceinfo->getAttribute('content');
							}
						}

					}
				}
				$price['publisher'] = '';
	
		        break;

		    case 'ALT otto-media.de':
		    case 'ALT donauland.at':
	
				$html = file_get_contents($url);


				$doc = new DOMDocument();
				$doc->loadHTML($html);


				// Suchergebnis analysieren!
				$elements = $doc->getElementsByTagName('div');

				$found = false;
				foreach($elements as $i => $element) {

					if($element->getAttribute('class') == 'search_message') {

						// Wenn nicht exakt 1 Artikel gefunden wird, dann abbrechen!
						if(!strpos(innerHTML($element),'1 Artikel')) {
							return false;
						} else {

							$found = true;
						}

					}
				}


				if(!$found) {
					return false;
				}


				
				unset($elements);

				// Autor kann nicht zuverlässig gegriffen werden
				$price['authors'] = '';

				$elements = $doc->getElementsByTagName('h3');
				foreach($elements as $i => $element) {

					if($element->hasAttribute('class') && $element->getAttribute('class') == 'title') {
						$price['title'] = (strip_tags(innerHTML($element)));
					}
				}

				unset($elements);


				$elements = $doc->getElementsByTagName('div');
				foreach($elements as $i => $element) {

					if($element->getAttribute('class') == 'image') {
						$images = $element->getElementsByTagName('img');
						foreach($images as $image) {
							if($image->hasAttribute('class')) {

							} else {
								if($image->getAttribute('width') == '110') {
									$price['image'] = 'http://www.'.$domain.'/'.$image->getAttribute('src');
								}
							}
						} 
					}
				}
				unset($elements);

				$elements = $doc->getElementsByTagName('div');
				foreach($elements as $i => $element) {
			
					if($element->hasAttribute('class') && $element->getAttribute('class') == 'normaldiv') {
			

	
						// Get the currency
						$priceinfos = $element->getElementsByTagName('span');
						foreach($priceinfos as $priceinfo) {
							if($priceinfo->hasAttribute('itemprop') && $priceinfo->getAttribute('itemprop') == 'price') {
								$price['value'] = trim(str_replace(',','.', trim(str_replace('&euro;', '', innerHTML($priceinfo)))));
							}
						}

						// Get the currency
						$priceinfos = $element->getElementsByTagName('meta');
						foreach($priceinfos as $priceinfo) {
							if($priceinfo->hasAttribute('itemprop') && $priceinfo->getAttribute('itemprop') == 'priceCurrency') {
								$price['currency'] = $priceinfo->getAttribute('content');
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

					if($topelement->hasAttribute('class') && $topelement->getAttribute('class') == 'row pvTop') {

		
						$elements = $topelement->getElementsByTagName('span');
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'mainPrice') {
					
								// Get the currency
								$priceinfos = $element->getElementsByTagName('span');

								$tmp_price = array();
								
								
								foreach($priceinfos as $priceinfo) {
								
									if($priceinfo->hasAttribute('class') && $priceinfo->getAttribute('class') == 'oPriceLeft') {
									
										$tmp_price[0] = innerHTML($priceinfo);
										
									} else if($priceinfo->hasAttribute('class') && $priceinfo->getAttribute('class') == 'oPriceRight') {
									
										$tmp_price[1] = innerHTML($priceinfo);
										
									} else if($priceinfo->hasAttribute('class') && $priceinfo->getAttribute('class') == 'oPriceSymbol') {

										$tmp_price[2] = innerHTML($priceinfo);

									}
											
									$price['value'] = $tmp_price[0].','.$tmp_price[1];
									$price['currency'] = $tmp_price[2];

								}
							}
						}
						unset($elements);
						
						
						$elements = $topelement->getElementsByTagName('span');
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'oProductCover oProductCoverDetail') {
					
								// Get the currency
								$subelements = $element->getElementsByTagName('img');

								foreach($subelements as $subelement) {
										
									if($subelement->hasAttribute('src')) {
										$price['image'] = $subelement->getAttribute('src');
									}

								}
							}
						}
						unset($elements);


						$elements = $topelement->getElementsByTagName('span');
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'oProductTitle') {
								$price['title'] = strip_tags(innerHTML($element));
				
							}
						}
						unset($elements);
						
						$elements = $topelement->getElementsByTagName('span');
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'oSubTitle') {
								$price['subtitle'] = strip_tags(innerHTML($element));
				
							}
						}
						unset($elements);


						$elements = $topelement->getElementsByTagName('dd');
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'description ncPunchBox') {

								// Get the currency
								$subelements = $element->getElementsByTagName('span');

								foreach($subelements as $subelement) {
								
									if($subelement->hasAttribute('class') && $subelement->getAttribute('class') == 'noDescription') {
										$price['text'] = strip_tags(innerHTML($subelement));
						
									}

								}
							}
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

				if(!$price['subtitle']) {
					$price['subtitle'] = '<span class="error" style="color:red;">Fehler: Kein Untertitel gefunden</span>';
					$price['seostatus'] = 'notice';
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


						$elements = $doc->getElementsByTagName('span');
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'pm_untertitel') {
								$price['subtitle'] = (strip_tags(innerHTML($element)));
							}
						}
		
						unset($elements);		
						
						$elements = $doc->getElementsByTagName('div');
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'redaktion raw') {
								$price['text'] = (strip_tags(innerHTML($element)));
							}
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
				
				if(!$price['subtitle']) {
					$price['subtitle'] = '<span class="error" style="color:red;">Fehler: Kein Untertitel gefunden</span>';
					$price['seostatus'] = 'notice';
				}					

		        break;
		        
		    case 'buecher.de':
			case 'hugendubel.de':

				$html = file_get_contents($url);

				if(!$html) {
					$price['value'] = '';
				}
				$doc = new DOMDocument();
				$doc->loadHTML($html);

				$topelements = $doc->getElementsByTagName('div');
				foreach($topelements as $i => $topelement) {

					if($topelement->hasAttribute('id') && $topelement->getAttribute('id') == 'content_product') {

		
						$elements = $topelement->getElementsByTagName('h1');
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'titel') {
								$price['title'] = (trim(strip_tags(innerHTML($element))));
							}
						}
		
						if(!$price['title']) {
							return false;
						}
		
						unset($elements);

		
						$elements = $topelement->getElementsByTagName('div');
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'description') {
								$price['subtitle'] = (trim(strip_tags(innerHTML($element))));
							}
						}
						unset($elements);
						
		
						$elements = $topelement->getElementsByTagName('h2');
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'author') {
								$price['authors'] = (trim(strip_tags(innerHTML($element)),'&nbsp;'));
							}
						}
		
						unset($elements);
		
		
						$elements = $topelement->getElementsByTagName('a');
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
		
						$elements = $topelement->getElementsByTagName('div');
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
						
						
						$elements = $topelement->getElementsByTagName('div');
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('id') && $element->getAttribute('id') == 'Produktbeschreibung') {

								// Get the currency
								$subelements = $element->getElementsByTagName('span');

								foreach($subelements as $subelement) {
								
									if($subelement->hasAttribute('class') && $subelement->getAttribute('class') == 'produktbeschreibung_def') {
									
										$price['text'] = strip_tags(innerHTML($subelement));
						
									}

								}
							}
						}
						unset($elements);
												
						
						$price['publisher'] = '';
					}
				}
				
				if(!$price['subtitle']) {
					$price['subtitle'] = '<span class="error" style="color:red;">Fehler: Kein Untertitel gefunden</span>';
					$price['seostatus'] = 'notice';
				}
				
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


				$topelements = $doc->getElementsByTagName('div');
				foreach($topelements as $i => $topelement) {

					if($topelement->hasAttribute('class') && $topelement->getAttribute('class') == 'resultlist') {

						$elements = $topelement->getElementsByTagName('h3');
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'title') {
		
								$price['title'] =  (trim(strip_tags(innerHTML($element))));
							}
						}
						unset($elements);


						$elements = $topelement->getElementsByTagName('div');
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'subtitleauthorbox') {
		
								/*
								$subelements = $element->getElementsByTagName('span');

								foreach($subelements as $k => $subelement) {
																	
									$price['subtitle'] = $subelement->getAttribute('title');
																	
								}

								unset($subelements);
								*/

								$price['subtitle'] =  trim(strip_tags(innerHTML($element)));

							}
						}
						unset($elements);

		
						$elements = $topelement->getElementsByTagName('div');
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'author') {
		
								$price['authors'] =  (trim(strip_tags(innerHTML($element))));
							}
						}
		
						unset($elements);
		
		
		
						$elements = $topelement->getElementsByTagName('div');
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
		
		
						$elements = $topelement->getElementsByTagName('div');
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
						
						unset($elements);

						
						$elements = $topelement->getElementsByTagName('h3');
						
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'title') {

								$subelements = $element->getElementsByTagName('a');

								foreach($subelements as $k => $subelement) {
								
									if($subelement->hasAttribute('href')) {
									
										$price['url'] = $subelement->getAttribute('href');
									
										//$html = file_get_contents($price['url']);
									}
								}
								unset($subelements);
							}
						}						
					}
				}
						
						
				if(!$price['subtitle']) {
					$price['subtitle'] = '<span class="error" style="color:red;">Fehler: Kein Untertitel gefunden</span>';
					$price['seostatus'] = 'notice';
					
				}
				
				$price['text'] = 'n.a.';


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
						
							//if(trim(innerHTML($priceinfo)) == 'Epub') {

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
								//}
								

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


				$topelements = $doc->getElementsByTagName('div');
				foreach($topelements as $i => $topelement) {

					if($topelement->hasAttribute('id') && $topelement->getAttribute('id') == 'resultsCol') {

						$elements = $topelement->getElementsByTagName('h2');
						foreach($elements as $i => $element) {
		
							if($element->hasAttribute('class') && $element->getAttribute('class') == 'a-size-medium s-inline s-access-title a-text-normal') {
		
								$price['title'] =  (trim(strip_tags(innerHTML($element))));

								/*
								$subelements = $element->getElementsByTagName('span');
								foreach($subelements as $i => $subelement) {
				
									if($subelement->getAttribute('class') == 'lrg bold') {
				
										$price['title'] =  (trim(strip_tags(innerHTML($subelement))));
									}
									if($subelement->getAttribute('class') == 'med reg') {
				
										$price['authors'] =  (trim(strip_tags(innerHTML($subelement))));
									}
								}
								*/
								
												
								$elements = $topelement->getElementsByTagName('img');
								foreach($elements as $i => $element) {
				
									if($element->getAttribute('class') == 's-access-image cfMarker') {
										if($element->hasAttribute('src')) {
											//$price['image'] = str_replace(',','&',$element->getAttribute('src'));
											$price['image'] = $element->getAttribute('src');
										}	
									}
								}
								
								
								unset($elements);
				

								$elements = $topelement->getElementsByTagName('span');
								
		
								foreach($elements as $element) {
								
									if($element->hasAttribute('class') && $element->getAttribute('class') == 'a-size-small a-color-price a-text-bold') {
		
										$priceparts = explode(' ',strip_tags(innerHTML($element)));
		
										foreach($priceparts as $part) {
											if($part == 'EUR') {
												$price['currency'] = 'EUR';
											} else {
												$price['value'] = trim(str_replace(',','.',$part));
											}
										} 
									}
								}

								unset($elements);							
							}
						}
					}
				}
				
				//flo($price);

				if($retry < 3 && !$price['title']) {

					$retry ++;
					$price = getPriceFromWeb($isbn, $domain);

				}
				
				//$keywords = explode(' ', $price['title']);
				
				$price['keywords'] = str_word_count ($price['title']);
				
				/*
				foreach(explode(' ', $price['title']) as $k => $v) {
					
				}
			
				//$price['keywords'] = '<ol><li>'.implode('</li><li>', $keywords).'</li></ol>';
	
				include 'classes/class.keywordDensity.php';
				
				$url = 'https://www.langenscheidt.de/Langenscheidt-Power-Woerterbuch-Spanisch-Buch/978-3-468-13304-6';
				
				$obj = new KD();                                      // New instance
				$obj->domain = $url;          // Define Domain
				flo ($obj->result()); 
							
				*/
				
				$price['publisher'] = '';
				$price['text'] = 'n.a.';
				$price['subtitle'] = 'n.a.';
			
		        break;
		              				
		}	
	}

	$price['domain'] = $domain;

	return $price;
}


?>