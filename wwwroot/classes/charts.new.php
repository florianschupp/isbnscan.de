<?php

function generateDataset($configuration, $partnerfilter = false, $view = 'retailer', $theme = 'dark', $mode = 'sent', $job = false) {



}



function createChart($configuration, $partnerfilter = false, $view = 'retailer', $theme = 'dark', $mode = 'sent', $job = false) {

	global $accounts, $PARTNERS_FILE, $partner_structure; 
	



	if($theme == 'dark') {
		$font_color = '#EEEEEE';		
	} else if ($theme == 'light') {
		$font_color = '#111111';		
	}
	
	if($mode === false) {
		$mode = 'sent';	
	}


	// Set Date Filter
	if($configuration['start_date']) {
		$configuration['start_date'] =strtotime($configuration['start_date']);
	}
	// Set Date Filter	
	if($configuration['end_date']) {
		$configuration['end_date'] =strtotime($configuration['end_date']);
	}
	
	//flo($configuration);
	
	$known_graphvalues = array('net_unit_price', 'cost_of_goods_sold');
	
	
	$valueAxis_label = $configuration['valueAxis.title']; // 'Wareneinsatz (Billing Amount / Actual Net Price)';

	if($configuration['valueAxis.minimum']) {
		$valueAxis_minimum = $configuration['valueAxis.minimum']; // '0;	
	} else {
		$valueAxis_minimum = 0;
	}
	
	// Create Array of Retailpartner with domain as key
	$domainpartnerids = array();
	foreach($accounts as $id => $account) {
		if($account['partnertype'] == 'Debitor') {
			$domainpartnerids[$account['name']] = $id;		
		}
	}
	

	if(!$job) {

		$jobs = loaddir(false, false, $mode, false);		
	
		foreach($jobs as $i => $job) {
			$parts = explode('_', $job);
		
			if(count($parts) <=1) {
				continue;
			}
			$job_periods[$parts[1]][] = $job;
		}

	} else {
			$parts = explode('_', $job);

			$job_periods[$parts[1]][] = $job;
					
	}

	$groups = array();

	$accounts_by_name = array();
	
	foreach($accounts as $id => $account) {
			$accounts_by_name[$account['name']] = $account;
	}
		
	$partner_timeline = array();
	
	foreach($job_periods as $partnerid => $fakturas) {

		foreach($fakturas as $i => $jobdir) {

			$billingamounts = getBillingAmounts($jobdir, $mode);
			//flo($billingamounts);
			//die();
			if(!$billingamounts) {
				echo usermessage('error', 'Keine Cache Datei f&uuml;r '.$jobdir.' vorhanden. <a href="view.php?job='.$jobdir.'">Faktur ansehen und Cache Datei erzeugen</a>');				
			} else {

			
				////////////////////////////////////////////////////////////////////////
				// 
				// Wichtig
				// Es sollen nur Tageswerte der Lieferanten, d.h. deren Verkaufsmengen 
				// Å¸ber die jeweiligen Vertriebspartner verwendet werden
				// Die Fakturen der Retailer werden Å¸bersprungen
				// 
	
				$parts = explode('_', $jobdir);
				$faktura_id = $parts[2];
				$skip = false;
	
				if(($faktura_id >= 1000000) && ($faktura_id < 2000000 )) {
					$model = 'proforma_unknown';
					// 'Proforma';
					$skip = true;
				} else if(($faktura_id >= 2000000) && ($faktura_id < 3000000 )) {
					// 'Reseller Debitor';			
					$model = 'Reseller';

					$skip = false;
				} else if(($faktura_id >= 3000000) && ($faktura_id < 4000000 )) {
					// 'Agency Debitor';
					$model = 'Agency';
					
					$skip = false;
				} else if(($faktura_id >= 4000000) && ($faktura_id < 5000000 )) {
					$model = 'Reseller';
					// 'Reseller Kreditor';
					$skip = true;
				} else if(($faktura_id >= 5000000) && ($faktura_id < 6000000 )) {
					$model = 'Service';
					// 'Service Debitor';
					$skip = false;
				} else if(($faktura_id >= 8000000) && ($faktura_id < 9000000 )) {
					// 'Agency Kreditor';
					$model = 'Agency';
					$skip = true;
				}			
				
				if($skip == true) {
					continue;
				}

				if($billingamounts['actual_gross_price']['EUR'] == 0 && $billingamounts['actual_gross_price']['CHF'] > 0) {

					$transaction_currency = 'CHF';
					
				} else {

					$transaction_currency = 'EUR';

				}

				$group_country = array();
				foreach($billingamounts['country_billing_amount'] as $country => $country_billing_amount) {
					
					if($country_billing_amount > 0) {
						
						$group_country[] = $country;
						
					} 
				}
				
												
				// Clean Array correspondong to filters
				//
				foreach($billingamounts['daily_amounts'] as $k => $amounts_array) {

					foreach($amounts_array as $date => $domains) {
					
						if(($configuration['start_date'] && strtotime($date) < $configuration['start_date']) OR ($configuration['end_date'] && strtotime($date) > $configuration['end_date'])) {
							unset($billingamounts['daily_amounts'][$k][$date]);					
							continue;
						}
	
						foreach($domains as $domain => $suppliers) {
							if($configuration['filter']['groupfilter_debi'] && $configuration['filter']['groupfilter_debi'] != $accounts[$domainpartnerids[$domain]]['group']) {
								unset($billingamounts['daily_amounts'][$k][$date][$domain]);
								continue;
							}

							if($configuration['filter']['partnerfilter'] && $configuration['filter']['partnerfilter'] != $domainpartnerids[$domain]) {
								unset($billingamounts['daily_amounts'][$k][$date][$domain]);
								continue;
							}							
							
							foreach($suppliers as $supplier_id => $supplier_value) {
								if($configuration['filter']['groupfilter_kredi'] && $configuration['filter']['groupfilter_kredi'] != $accounts[$supplier_id]['group']) {
									unset($billingamounts['daily_amounts'][$k][$date][$domain][$supplier_id]);
									continue;
								} else {

								}
							}
						}
					}				
				}

			//	flo($billingamounts['daily_amounts']['net_unit_price_by_shopchannel']);


				if(array_key_exists('daily_amounts', $billingamounts)) {
					
					if(count($billingamounts['daily_amounts']) == 0)	{
						echo usermessage('error', 'Keine Cache Datei f&uuml;r '.$jobdir.' vorhanden. <a href="view.php?job='.$jobdir.'" target="_blank">Faktur ansehen und Cache Datei erzeugen</a>');
					}


					//flo($billingamounts['daily_amounts'] );

					if(!array_key_exists('net_unit_price', $billingamounts['daily_amounts'])) {
						echo usermessage('error', 'Datenfehler: "net_unit_price" nicht verf&uuml;gbar bei '.$jobdir.'. <a href="view.php?job='.$jobdir.'" target="_blank">Faktur ansehen und Cache Datei erzeugen</a>');
					} else {

						// FÅ¸r COGS muss der TatsÅ chliche VK-Preis in LandeswÅ hrung herangezogen werden, sonst ergibt der Wareneinsatz nur einen theoretischen Wert 
						if($configuration['graphvalue'] == 'cost_of_goods_sold') {
							$baseprice = 'actual_gross_price';
							
								if(!array_key_exists('actual_gross_price', $billingamounts['daily_amounts'])) {
									echo usermessage('error', 'Datenfehler: "actual_gross_price" nicht verf&uuml;gbar bei '.$jobdir.'. <a href="view.php?job='.$jobdir.'" target="_blank">Faktur ansehen und Cache Datei erzeugen</a>');
								}
					
						} else if($configuration['graphvalue'] == 'shopchannel') {

							$baseprice = 'net_unit_price_by_shopchannel';

						} else {
							$baseprice = 'net_unit_price';

						}

						foreach($billingamounts['daily_amounts'][$baseprice] as $date => $domains) {
	
							//flo($domains);

							$domainvalue = 0;
							$publishervalue = 0;
	
							$timestamp = strtotime($date);
							if($configuration['time_aggregate'] == 'week') {
								$timeslot = ''.date('Y\WW', $timestamp);
	
								//$weeks[$timeslot][date('Y-m-d', $timestamp)] ++;
	
							} else if($configuration['time_aggregate'] == 'month') {
								$timeslot = ''.date('Y-m', $timestamp);
							} else {
								$timeslot = $date;
							}
	
							//$group_partners = 'partnername';
	
							foreach($domains as $domain=> $publisheramounts) {

								$distributionpartnerid = $domainpartnerids[$domain];
	
								if(!array_key_exists($timeslot, $partner_timeline)) {
									$partner_timeline[$timeslot] = array();
								}
								
								if($view == 'retailer') {	
												
									if(is_array($publisheramounts)) {
										$publisheramount = 0;
										$publisherbillingamount = 0;
										
										
										if($configuration['graphvalue'] == 'cost_of_goods_sold') {
										
											foreach($publisheramounts as $publisher => $currencies) {

												foreach($currencies as $currency => $countries) {
	
													//flo($currencies);
													
													foreach($countries as $country => $amount) {
													
														$actual_gross_price = $amount;
	
														$estimated_actual_net_price = calculateNetPrice($amount, $country, $date);
														
														$actual_net_price_eur = convertAmount($estimated_actual_net_price, $currency, $date);
	
														$publisheramount += $actual_net_price_eur;
																											
													}
												}
												
												if(array_key_exists('billing_amount', $billingamounts['daily_amounts'])) {
																	
													$publisherbillingamount += $billingamounts['daily_amounts']['billing_amount'][$date][$domain][$publisher] ;
			
												}
											}

										} else if($configuration['graphvalue'] == 'shopchannel') {
										


										} else {
										
											foreach($publisheramounts as $publisher => $amount) {
	
												$publisheramount += $amount;
		
												if(array_key_exists('billing_amount', $billingamounts['daily_amounts'])) {
													
													$publisherbillingamount += $billingamounts['daily_amounts']['billing_amount'][$date][$domain][$publisher] ;
		
												}
											}	
										}
																
	
		
									} else {
											//$publisheramount = $publisheramounts;
											$publisheramount = 0;
											$publisherbillingamount = 0;
									}
	
									if($configuration['category_group'] == 'partnergroup') {

										$partnerstring = $accounts_by_name[$domain]['group'];

										if(!array_key_exists($partnerstring, $partner_timeline[$timeslot])) {
											$partner_timeline[$timeslot][$partnerstring] = 0;
											$partner_timeline_billingamount[$timeslot][$partnerstring] = 0;
	
										}								
										$partner_timeline_billingamount[$timeslot][$partnerstring] += $publisherbillingamount;															
										$partner_timeline[$timeslot][$partnerstring] += $publisheramount;
										
										
									} else if($configuration['category_group'] == 'country') {


										$partnerstring = $group_country[0];

										if(!array_key_exists($partnerstring, $partner_timeline[$timeslot])) {
											$partner_timeline[$timeslot][$partnerstring] = 0;
											$partner_timeline_billingamount[$timeslot][$partnerstring] = 0;
	
										}								
										$partner_timeline_billingamount[$timeslot][$partnerstring] += $publisherbillingamount;															
										$partner_timeline[$timeslot][$partnerstring] += $publisheramount;		
											
									} else if($configuration['category_group'] == 'currency') {

										//flo($billingamounts);
										//die();
										$partnerstring = $transaction_currency;

										if(!array_key_exists($partnerstring, $partner_timeline[$timeslot])) {
											$partner_timeline[$timeslot][$partnerstring] = 0;
											$partner_timeline_billingamount[$timeslot][$partnerstring] = 0;
	
										}								
										$partner_timeline_billingamount[$timeslot][$partnerstring] += $publisherbillingamount;															
										$partner_timeline[$timeslot][$partnerstring] += $publisheramount;		
														
									} else if($configuration['category_group'] == 'salesmodel') {

										$partnerstring = $model;

										if(!array_key_exists($partnerstring, $partner_timeline[$timeslot])) {
											$partner_timeline[$timeslot][$partnerstring] = 0;
											$partner_timeline_billingamount[$timeslot][$partnerstring] = 0;
	
										}								
										$partner_timeline_billingamount[$timeslot][$partnerstring] += $publisherbillingamount;															
										$partner_timeline[$timeslot][$partnerstring] += $publisheramount;	
										
									} else if($configuration['category_group'] == 'shopchannel') {

										foreach($publisheramounts as $channel => $amount ) {

											$partnerstring = $channel;

											if(!array_key_exists($partnerstring, $partner_timeline[$timeslot])) {
												$partner_timeline[$timeslot][$partnerstring] = 0;
												$partner_timeline_billingamount[$timeslot][$partnerstring] = 0;
											}



											$partner_timeline[$timeslot][$partnerstring] += $amount ;	

											if(array_key_exists('net_unit_price_by_shopchannel', $billingamounts['daily_amounts'])) {
																
												//$publisherbillingamount += $billingamounts['daily_amounts']['net_unit_price_by_shopchannel'][$date][$domain][$channel] ;
		
											}
										}


															
									} else {
		

										if(!array_key_exists($domain, $partner_timeline[$timeslot])) {
											$partner_timeline[$timeslot][$domain] = 0;
											$partner_timeline_billingamount[$timeslot][$domain] = 0;
	
										}
										
										if($configuration['graphvalue'] == 'cost_of_goods_sold') {
	
											$partner_timeline_billingamount[$timeslot][$domain] += $publisherbillingamount;															
											$partner_timeline[$timeslot][$domain] += $publisheramount;															
	
										
										} else {
	
											$partner_timeline[$timeslot][$domain] += $publisheramount;															
											
										}
										
									}								
	
								} else {
	
									if(is_array($publisheramounts)) {
										
										foreach($publisheramounts as $publisher => $amount) {
	
											$partnerstring = false;
											
											if($configuration['category_group'] == 'partnergroup') {
																				
												$partnerstring = $accounts[$publisher]['group'];
												
												$partnerstring = str_replace(' ', '_', $partnerstring);
												
												if(!array_key_exists($partnerstring, $partner_timeline[$timeslot])) {
													$partner_timeline[$timeslot][$partnerstring] = 0;
												}								
				
												$partner_timeline[$timeslot][$partnerstring] += $amount;							



											} else if($configuration['category_group'] == 'tier') {
		
												$partnerstring = $partner_structure['tier']['values'][$accounts[$publisher]['tier']];
												$partnerstring = str_replace(' ', '_', $partnerstring);	

												//$partnerstring = 'tier_'.$accounts[$publisher]['tier'];

												if(!array_key_exists($partnerstring, $partner_timeline[$timeslot])) {
													$partner_timeline[$timeslot][$partnerstring] = 0;
													$partner_timeline_billingamount[$timeslot][$partnerstring] = 0;
			
												}								
												$partner_timeline[$timeslot][$partnerstring] += $amount;		
																	
			
											} else {
	
												$partnerstring = $accounts[$publisher]['id'];
	
												if(!array_key_exists($partnerstring, $partner_timeline[$timeslot])) {
													$partner_timeline[$timeslot][$partnerstring] = 0;
												}
	
												
												$partner_timeline[$timeslot][$partnerstring] += $amount;							
											
											}
										}		
		
									} else {
											$publisheramount = 0;
									}		
									
								}
							}		
						}		
					}		
				}				
			}
		}
	}


	if($configuration['graphvalue'] == 'cost_of_goods_sold') {
	
		$we_timeline = array();
		
		//flo($partner_timeline);
		//flo($partner_timeline_billingamount);
		
		foreach($partner_timeline as $timeslot => $partners) {
		
			foreach($partners as $partner => $value) {
	
				$cost_of_good = $partner_timeline_billingamount[$timeslot][$partner];
				//flo($partner);
				
				if($value != 0) {

					$we_timeline[$timeslot][$partner] = round(100*$cost_of_good/$value, 1);
					
				} else {

				}
			}
		}
		$partner_timeline = $we_timeline;	

	} else if ($configuration['graphvalue'] == 'indexpoints') {

		$index_timeline = array();
		
		$i = 0;
		foreach($partner_timeline as $timeslot => $partners) {
		
//			flo('<hr>'.$timeslot);

			foreach($partners as $partner => $value) {

//				flo($partner );

				// Wert der ersrten Periode als Basiswert verwenden
				// falls fÃ¼r Partner in der ertsen Perionde nohc kein Wert vohanden, dann den ersten verfÃ¼gbaren Wert verwenden

				if($i == 0 OR !$base[$partner]) { 

					$base[$partner] = $value;
				}

				$indexpoints = round(100*$value/$base[$partner],2)-100;


//				flo('Base '.$base[$partner]);
//				flo('Wert ' .$value);
//				flo('Punkte '.$indexpoints);
				
				if($value != 0) {

					$index_timeline[$timeslot][$partner] = $indexpoints;
					
				} else {

				}
			}
			$i++;
		}

		$partner_timeline = $index_timeline;		

	}

	$timeline_chart_data = '';
	$timeline_chart_code = array();

	ksort($partner_timeline);

	foreach($partner_timeline as $timestamp => $domains) {

		if($configuration['time_aggregate'] == 'week') {
			$day = 'KW'.date('W Y', strtotime($timestamp));
		} else if($configuration['time_aggregate'] == 'day') {
			$day = date('D d.m.Y', strtotime($timestamp));
		} else if($configuration['time_aggregate'] == 'month') {
			$day = date('M Y', strtotime($timestamp));
		} else {
			$day = date('D d.m.Y', strtotime($timestamp));
		}

	
	
			$valuestring = '';
			
			foreach($domains as $domain => $value) {
	
				
				if(trim($domain) == '') {
				
					$domain = 'unbekannt';
					$domain_id = 'unbekannt';

				} else {
	
					$domain_id = str_replace ('.', '_', $domain);
					$domain_id = str_replace ('-', '_', $domain_id);
					$domain_id = str_replace (' ', '_', $domain_id);
					$domain_id = str_replace ('/', '_', $domain_id);
					
					if(array_key_exists($domain_id, $accounts)) {
						$partner_label = $accounts[$domain_id]['name'];
					} else {
						$partner_label = $domain_id;
					}

							$timeline_chart_code[$domain_id] = '';
							$timeline_chart_code[$domain_id] .= '    var '.$domain_id.' = new AmCharts.AmGraph();'."\n";
							$timeline_chart_code[$domain_id] .= '    '.$domain_id.'.title = "'.$partner_label.'";'."\n";
							$timeline_chart_code[$domain_id] .= '    '.$domain_id.'.valueField = "'.$domain_id.'";'."\n";

							$timeline_chart_code[$domain_id] .= '    '.$domain_id.'.type = "'.$configuration['graph_type'].'";'."\n";
							
							if($configuration['graph_type'] == 'column' OR $configuration['graph_type'] == 'step') {
								
								$timeline_chart_code[$domain_id] .= '    '.$domain_id.'.type = "column";'."\n";
								$timeline_chart_code[$domain_id] .= '    '.$domain_id.'.lineAlpha = 0.5;'."\n";
								$timeline_chart_code[$domain_id] .= '    '.$domain_id.'.fillAlphas = 0.3;'."\n";
								$timeline_chart_code[$domain_id] .= '    '.$domain_id.'.color  = \''.$font_color.'\''."\n";	

								if($configuration['time_aggregate'] == 'month') {
									$timeline_chart_code[$domain_id] .= '    '.$domain_id.'.labelText = "[[value]] '.$configuration['value_suffix'].'";'."\n";				
								}

							} else if ($configuration['graph_type'] == 'line' OR $configuration['graph_type'] == 'smoothedLine'){
									
								$timeline_chart_code[$domain_id] .= '    '.$domain_id.'.lineAlpha = 1;'."\n";
								$timeline_chart_code[$domain_id] .= '    '.$domain_id.'.fillAlphas = 0.05;'."\n";
								$timeline_chart_code[$domain_id] .= '    '.$domain_id.'.bullet = "round";'."\n";
								$timeline_chart_code[$domain_id] .= '    '.$domain_id.'.bulletSize = 3;'."\n";
								$timeline_chart_code[$domain_id] .= '    '.$domain_id.'.connect = false;'."\n";    
	
							}
							
							
							$timeline_chart_code[$domain_id] .= '    '.$domain_id.'.balloonText = "'.$partner_label.' [[category]]: [[value]] '.$configuration['value_suffix'].'";'."\n";
							$timeline_chart_code[$domain_id] .= '    chart.addGraph('.$domain_id.');'."\n\n\n";
		
				}
			
				if(trim($domain) == '') {
					$domain = 'unbekannt';
				} else {
					$valuestring .= ', '.$domain_id.': '.number_format($value,2,'.','').''; 
				}
			}	
		
			$timeline_chart_data .= '{date: "'.$day.'"'.$valuestring.'},'."\n";		
	
	}
	
	// Reverse sortieren, damit dei grÃ¶ÃŸte FlÃ¤he im Diagramm hinten steht
	
	if(count($timeline_chart_code) == 0) {
		echo usermessage('error', 'Ihre Selektion liefert keine Daten ');
		return;
	}
	arsort($timeline_chart_code);
	


	$html = '';


global $CHART_COLORS_JSARRAY;

	$html .= <<<EOT
	

<script src="/amcharts/amcharts.js" type="text/javascript"></script>
<script type="text/javascript">

var chart;
var chartData_timeline = [$timeline_chart_data];

AmCharts.ready(function() {

    //////////////////////////////////////
    // "Timeline"
    //
    chart = new AmCharts.{$configuration["chart_type"]["class"]}();

    chart.colors = [{$CHART_COLORS_JSARRAY}];

    chart.dataProvider = chartData_timeline;
    chart.categoryField = "date";
    chart.startDuration = 0;
    //chart.height = 400;

    chart.numberFormatter = {precision:-1, decimalSeparator:',', thousandsSeparator:'.'};
    chart.plotAreaBorderColor = "{$font_color}";
    chart.plotAreaBorderAlpha = 1;

    chart.pathToImages = "/js/charts/images/";
    chart.zoomOutButton = {
        backgroundColor: '#FFFFFF',
        backgroundAlpha: 0.55
    };
   
    var categoryAxis = chart.categoryAxis;
   
    categoryAxis.labelRotation = 45;
    categoryAxis.gridPosition = "start";
    categoryAxis.gridAlpha = 0.2;
    categoryAxis.axisAlpha = 1;
    categoryAxis.color = "{$font_color}";
    categoryAxis.axisColor = "{$font_color}";
    categoryAxis.gridColor = "{$font_color}";
    categoryAxis.titleColor = "{$font_color}";
    
    var valueAxis = new AmCharts.ValueAxis();
	
EOT;

	if($configuration['chart_type']['stacked'] == 'regular') {
        $html .= 'valueAxis.stackType = "regular";'."\n";
	} else if($configuration['chart_type']['stacked'] == '100%') {
        $html .= 'valueAxis.stackType = "100%";'."\n";
	} else {

	} 

	if(array_key_exists('chart.rotate', $configuration) && $configuration['chart.rotate'] == 'true') {
        $html .= 'chart.rotate = true;'."\n";
	}



$html .= <<<EOT

    valueAxis.dashLength = 5;
    valueAxis.unit = " {$configuration['valueAxis.unit']}";
EOT;

if($valueAxis_minimum) {
	$html .= '    valueAxis.minimum =	'.$valueAxis_minimum.'';
} 


$html .= <<<EOT
    valueAxis.title = " {$valueAxis_label}";
    valueAxis.title.color = "{$font_color}";
    valueAxis.axisAlpha = 0;
    valueAxis.reversed = false;
    valueAxis.color = "{$font_color}";
    valueAxis.gridColor = " {$font_color}";
    valueAxis.titleColor = " {$font_color}";
EOT;
if($configuration['time_aggregate'] == 'month') {
$html .= '    valueAxis.totalText = "[[total]]"';
}
$html .= <<<EOT

    chart.addValueAxis(valueAxis);
        
    // LEGEND
    var legend = new AmCharts.AmLegend();
    legend.color = "  {$font_color}";
    legend.position = "bottom";
    legend.align = "right";

    chart.addLegend(legend, "{$configuration['legend_div']}");

EOT;

print($html);

?>


<?php

	if(0 AND array_key_exists('chartCursor', $configuration)) {
	    // CURSOR
	    $html = '';
	    $html .= '	var chartCursor = new AmCharts.ChartCursor();'."\n";
	    $html .= '	chartCursor.cursorPosition = "'.$configuration['chartCursor']['cursorPosition'].'";'."\n";
	    $html .= '	chartCursor.oneBalloonOnly = '.$configuration['chartCursor']['oneBalloonOnly'].';'."\n";
	    $html .= '	chart.addChartCursor(chartCursor);'."\n";		
	print($html);
	}


?>

    // SCROLLBAR
    var chartScrollbar = new AmCharts.ChartScrollbar();
    chart.addChartScrollbar(chartScrollbar);
    

<?php 

echo implode("\n", $timeline_chart_code);

?>

    

    chart.write("<?php echo $configuration['chart_div']; ?>");
    //////////////////////////////////////



});


function hideAll () {
  for (var i = 0; i < chart.graphs.length; i++) {
    chart.hideGraph(chart.graphs[i]);
  }
}

function showAll () {
  for (var i = 0; i < chart.graphs.length; i++) {
    chart.showGraph(chart.graphs[i]);
  }
}

</script>





<?	

}


?>