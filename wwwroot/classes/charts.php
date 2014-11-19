<?php

function createTimelineChart($graphvalue = 'net_unit_price', $start_date = false, $end_date = false, $timeframe = 'day', $partnerfilter = false, $groupfilter_debi = false, $groupfilter_kredi = false, $group_partners = false, $view = 'retailer', $theme = 'dark', $mode = 'sent', $job = false, $chart_div = false, $legend_div = false) {
	
	global $accounts, $PARTNERS_FILE; 

	if($mode === false) {
		$mode = 'sent';	
	}
	
	// Set Date Filter
	if($start_date) {
		$start_date =strtotime($start_date);
	}
	// Set Date Filter	
	if($end_date) {
		$end_date =strtotime($end_date);
	}
	
	$known_graphvalues = array('net_unit_price', 'cost_of_goods_sold');

	if($graphvalue == 'cost_of_goods_sold') {
		$value_suffix = '%';
		$valueAxis_minimum = 0;
		$valueAxis_label = 'Wareneinsatz (Billing Amount / Actual Net Price)';
	} else {
		$value_suffix = 'EUR';
		$valueAxis_minimum = 0;
		$valueAxis_label = 'Net Unit Price';
	}	

	if($theme == 'dark') {
		$font_color = '#AAAAAA';		
	} else if ($theme == 'light') {
		$font_color = '#111111';		
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

			if(!$billingamounts) {
				echo usermessage('error', 'Keine Cache Datei f&uuml;r '.$jobdir.' vorhanden. <a href="view.php?job='.$jobdir.'">Faktur ansehen und Cache Datei erzeugen</a>');				
			} else {

			
				////////////////////////////////////////////////////////////////////////
				// 
				// Wichtig
				// Es sollen nur Tageswerte der Lieferanten, d.h. deren Verkaufsmengen 
				// über die jeweiligen Vertriebspartner verwendet werden
				// Die Fakturen der Retailer werden übersprungen
				// 
	
				$parts = explode('_', $jobdir);
				$faktura_id = $parts[2];
				$skip = false;
	
				if(($faktura_id >= 1000000) && ($faktura_id < 2000000 )) {
					// 'Proforma';
					$skip = false;
				} else if(($faktura_id >= 2000000) && ($faktura_id < 3000000 )) {
					// 'Reseller Debitor';			
					$skip = false;
				} else if(($faktura_id >= 3000000) && ($faktura_id < 4000000 )) {
					// 'Agency Debitor';
					$skip = false;
				} else if(($faktura_id >= 4000000) && ($faktura_id < 5000000 )) {
					// 'Reseller Kreditor';
					$skip = true;
				} else if(($faktura_id >= 5000000) && ($faktura_id < 6000000 )) {
					// 'Service Debitor';
					$skip = false;
				} else if(($faktura_id >= 8000000) && ($faktura_id < 9000000 )) {
					// 'Agency Kreditor';
					$skip = true;
				}			
				
				if($skip == true) {
					continue;
				}


				
				// Clean Array correspondong to filters
				//
				foreach($billingamounts['daily_amounts'] as $k => $amounts_array) {
					foreach($amounts_array as $date => $domains) {
					
						if(($start_date && strtotime($date) < $start_date) OR ($end_date && strtotime($date) > $end_date)) {
							unset($billingamounts['daily_amounts'][$k][$date]);					
							continue;
						}
	
						foreach($domains as $domain => $suppliers) {
							if($groupfilter_debi && $groupfilter_debi != $accounts[$domainpartnerids[$domain]]['group']) {
								unset($billingamounts['daily_amounts'][$k][$date][$domain]);
								continue;
							}

							if($partnerfilter && $partnerfilter != $domainpartnerids[$domain]) {
								unset($billingamounts['daily_amounts'][$k][$date][$domain]);
								continue;
							}							
							
							foreach($suppliers as $supplier_id => $supplier_value) {
								if($groupfilter_kredi && $groupfilter_kredi != $accounts[$supplier_id]['group']) {
									unset($billingamounts['daily_amounts'][$k][$date][$domain][$supplier_id]);
									continue;
								} else {

								}
							}
						}
					}				
				}

				//flo($billingamounts['daily_amounts']);
				//die();



				if(array_key_exists('daily_amounts', $billingamounts)) {
					
					if(count($billingamounts['daily_amounts']) == 0)	{
						echo usermessage('error', 'Keine Cache Datei f&uuml;r '.$jobdir.' vorhanden. <a href="view.php?job='.$jobdir.'" target="_blank">Faktur ansehen und Cache Datei erzeugen</a>');					}


					//flo($billingamounts['daily_amounts'] );

					if(!array_key_exists('net_unit_price', $billingamounts['daily_amounts'])) {
						echo usermessage('error', 'Datenfehler: "net_unit_price" nicht verf&uuml;gbar bei '.$jobdir.'. <a href="view.php?job='.$jobdir.'" target="_blank">Faktur ansehen und Cache Datei erzeugen</a>');
					} else {

						// Für COGS muss der Tatsächliche VK-Preis in Landeswährung herangezogen werden, sonst ergibt der Wareneinsatz nur einen theoretischen Wert 
						if($graphvalue == 'cost_of_goods_sold') {
							$baseprice = 'actual_gross_price';
							
								if(!array_key_exists('actual_gross_price', $billingamounts['daily_amounts'])) {
									echo usermessage('error', 'Datenfehler: "actual_gross_price" nicht verf&uuml;gbar bei '.$jobdir.'. <a href="view.php?job='.$jobdir.'" target="_blank">Faktur ansehen und Cache Datei erzeugen</a>');
								}
					
						} else {
							$baseprice = 'net_unit_price';
						}

						foreach($billingamounts['daily_amounts'][$baseprice] as $date => $domains) {
	
									
							$domainvalue = 0;
							$publishervalue = 0;
	
							$timestamp = strtotime($date);
	
							if($timeframe == 'week') {
								$timeslot = ''.date('Y\WW', $timestamp);
	
								//$weeks[$timeslot][date('Y-m-d', $timestamp)] ++;
	
							} else if($timeframe == 'month') {
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
										
										
										if($graphvalue == 'cost_of_goods_sold') {
										
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
	
									if($group_partners == 'partnergroup') {
																		
										$partnerstring = $accounts_by_name[$domain]['group'];
										
										if(!array_key_exists($partnerstring, $partner_timeline[$timeslot])) {
											$partner_timeline[$timeslot][$partnerstring] = 0;
											$partner_timeline_billingamount[$timeslot][$partnerstring] = 0;
	
										}								
		
										$partner_timeline[$timeslot][$partnerstring] += $publisheramount;							
		
									} else {
		
										if(!array_key_exists($domain, $partner_timeline[$timeslot])) {
											$partner_timeline[$timeslot][$domain] = 0;
											$partner_timeline_billingamount[$timeslot][$domain] = 0;
	
										}
										
										if($graphvalue == 'cost_of_goods_sold') {
	
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
											
											if($group_partners == 'partnergroup') {
																				
												$partnerstring = $accounts[$publisher]['group'];
												
												$partnerstring = str_replace(' ', '_', $partnerstring);
												
												if(!array_key_exists($partnerstring, $partner_timeline[$timeslot])) {
													$partner_timeline[$timeslot][$partnerstring] = 0;
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


	if($graphvalue == 'cost_of_goods_sold') {
	
		$we_timeline = array();
		
		//flo($partner_timeline);
		//flo($partner_timeline_billingamount);
		
		foreach($partner_timeline as $timeslot => $partners) {
		
			foreach($partners as $partner => $value) {
	
				$cost_of_good = $partner_timeline_billingamount[$timeslot][$partner];
				
				if($value != 0) {

					$we_timeline[$timeslot][$partner] = round(100*$cost_of_good/$value, 1);
					
				} else {

				}
				
				
			}
			
		}
		
		$partner_timeline = $we_timeline;	
				
	}


	$timeline_chart_data = '';
	$timeline_chart_code = array();

	ksort($partner_timeline);

	foreach($partner_timeline as $timestamp => $domains) {

		if($timeframe == 'week') {
			$day = 'KW'.date('W Y', strtotime($timestamp));
		} else if($timeframe == 'day') {
			$day = date('D d.m.Y', strtotime($timestamp));
		} else if($timeframe == 'month') {
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
							$timeline_chart_code[$domain_id] .= '    '.$domain_id.'.balloonText = "'.$partner_label.' [[category]]: [[value]] '.$value_suffix.'";'."\n";
							$timeline_chart_code[$domain_id] .= '    '.$domain_id.'.lineAlpha = 1;'."\n";
							$timeline_chart_code[$domain_id] .= '    '.$domain_id.'.fillAlphas = 0.1;'."\n";
							$timeline_chart_code[$domain_id] .= '    '.$domain_id.'.bullet = "round";'."\n";
							$timeline_chart_code[$domain_id] .= '    '.$domain_id.'.bulletSize = 3;'."\n";
							$timeline_chart_code[$domain_id] .= '    '.$domain_id.'.connect = false;'."\n";    
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
	
	// Reverse sortieren, damit dei größte Fläche im Diagramm hinten steht
	
	if(count($timeline_chart_code) == 0) {
		echo usermessage('error', 'Ihre Selektion liefert keine Daten ');
		return;
	}
	arsort($timeline_chart_code);
	
?>


<script src="/amcharts/amcharts.js" type="text/javascript"></script>





<script type="text/javascript">

var chart;



var chartData_timeline = [<? echo $timeline_chart_data; ?>];



AmCharts.ready(function() {

    //////////////////////////////////////
    // "Timeline"
    //
    chart = new AmCharts.AmSerialChart();
    chart.dataProvider = chartData_timeline;
    chart.categoryField = "date";
    chart.startDuration = 0;
    //chart.height = 400;

    chart.numberFormatter = {precision:-1, decimalSeparator:',', thousandsSeparator:'.'};
    chart.plotAreaBorderColor = "<? echo $font_color; ?>";
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
    categoryAxis.color = "<? echo $font_color; ?>";
    categoryAxis.axisColor = "<? echo $font_color; ?>";
    categoryAxis.gridColor = "<? echo $font_color; ?>";
    categoryAxis.titleColor = "<? echo $font_color; ?>";
    
    var valueAxis = new AmCharts.ValueAxis();
    valueAxis.dashLength = 5;
    valueAxis.unit = " <? echo $value_suffix; ?>";
    valueAxis.minimum = <? echo $valueAxis_minimum; ?>;
    valueAxis.title = "<?php echo $valueAxis_label; ?>";
    valueAxis.title.color = "#AAAAAA";
    valueAxis.axisAlpha = 0;
    valueAxis.reversed = false;
    valueAxis.color = "<? echo $font_color; ?>";
    valueAxis.gridColor = "<? echo $font_color; ?>";
    valueAxis.titleColor = "<? echo $font_color; ?>";
    chart.addValueAxis(valueAxis);

        
    // LEGEND
    var legend = new AmCharts.AmLegend();
    legend.color = "<? echo $font_color; ?>";
    legend.position = "bottom";
    legend.align = "right";

	chart.addLegend(legend, "<?php echo $legend_div; ?>");

//    chart.addLegend(legend);

<?php 

echo implode("\n", $timeline_chart_code);

?>

    
    // CURSOR
    var chartCursor = new AmCharts.ChartCursor();
    chartCursor.cursorPosition = "mouse";
    chartCursor.oneBalloonOnly = true;
    chart.addChartCursor(chartCursor);

    // SCROLLBAR
    var chartScrollbar = new AmCharts.ChartScrollbar();
    chart.addChartScrollbar(chartScrollbar);
    chart.write("<?php echo $chart_div; ?>");
    //////////////////////////////////////



});
</script>

<?	

}


?>