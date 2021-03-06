<?php
include dirname(__FILE__) . "/../_lib/init.php";

$mysql_db = Setup::getOpenHabMysql();

$activeDay = empty($_GET["date"]) ? new DateTime() : DateTime::createFromFormat('Y-m-d',$_GET["date"]);

$isToday = empty($_GET["date"]) || $activeDay->format("Y-m-d") == (new DateTime())->format("Y-m-d");

/**** SUMMERY ****/
$from = clone $activeDay;
$from->setTime(0,0,0);
$to = clone $from;
$to->setTime(23,59,59);
$dayList = $mysql_db->getWeatherDataList($from, $to);

if( $dayList )
    {
    list( $minTemperature, $maxTemperature, $maxWindSpeed, $sumSunshine, $sumRain ) = Weather::calculateSummary( $dayList );

    //echo Weather::formatDay($activeDay);

    /**** DAYLIST ****/
    $from = clone $activeDay;
    if( $isToday )
    {
        $from->setTime(0,0,0);
    }
    else{
        $from->setTime(0,0,0);
        //$from->sub(new DateInterval('PT24H'));
    }
    $to = clone $activeDay;
    $to->setTime(24,00,00);

    $dayList = $mysql_db->getWeatherDataList($from, $to);

    $todayValues = array();
    $current_value = Weather::initBlockData( reset( $dayList )['datetime']);
    $index = 0;

    foreach( $dayList as $hourlyData  ){
        //echo ( $index ) . "<br>";
        
        if( $index > 0 && $index % 3 == 0 )
        {
            $current_value['to'] = $hourlyData['datetime'];
            $todayValues[] = $current_value;
            $current_value = Weather::initBlockData($hourlyData['datetime']);
            //echo $hourlyData['datetime']."<br>";
        }
        
        Weather::applyBlockData($current_value,$hourlyData);

        $index++;
    }
    $current_value['to'] = ( clone $hourlyData['datetime'] )->add(new DateInterval('PT1H'));;
    $todayValues[] = $current_value;

    //echo print_r($dayList,true);
    //echo print_r($todayValues,true);

    /**** WEEKLIST ****/
    $weekFrom = new DateTime();
    $weekFrom->setTime(0,0,0);

    $weekList = $mysql_db->getWeatherDataWeekList($weekFrom);
    //echo print_r($weekList,true);

    list( $minTemperatureWeekly, $maxTemperatureWeekly, $maxWindSpeedWeekly, $sumSunshineWeekly, $sumRainWeekly ) = Weather::calculateSummary( $weekList );

    $weekValues = array();
    $weekList[0]['datetime']->setTime(10,0,0);
    $current_value = Weather::initBlockData( $weekList[0]['datetime'] );
    $index = 1;
    foreach( $weekList as $hourlyData )
    {
        $hourlyData['datetime']->setTime(10,0,0);

        //echo $hourlyData['datetime']." ".$current_value['from'] . "\n\n";
        if( $hourlyData['datetime'] != $current_value['from'] )
        {
            $current_value['to'] = $current_value['from'];
            $weekValues[] = $current_value;
            $current_value = Weather::initBlockData($hourlyData['datetime']);
            
            //echo $hourlyData['datetime']."<br>";
        }
        
        Weather::applyBlockData($current_value,$hourlyData);

        $index++;
    }
}
else
{
    $hourlyData = [];
}
//$current_value['to'] = ( clone $hourlyData['datetime'] )->add(new DateInterval('PT1H'));;
//$weekValues[] = $current_value;

//echo print_r($weekValues,true);

?>
<div class="weatherForecast weatherDetailForecast">
	<div class="today">
		<div class="title">
            <?php /*echo time();*/ echo Weather::formatDay($activeDay); ?>
		</div>
		<div class="summary">
			<div class="cell"><div class="txt">Bereich:</div><div class="icon temperature"><?php echo Weather::getSVG('temperature', 'self_temperature_grayscaled') . "</div><div class=\"value\">" . $minTemperature . " °C - " . $maxTemperature; ?> °C</div></div>
			<div class="bullet">•</div>
			<div class="cell"><div class="txt">Max.:</div><div class="icon wind"><?php echo Weather::getSVG('wind', 'self_wind_grayscaled') . "</div><div class=\"value\">" . $maxWindSpeed; ?> km/h</div></div>
			<div class="bullet">•</div>
            <div class="cell"><div class="txt">Sum:</div><div class="icon rain"><?php echo Weather::getSVG('rain', 'self_rain_grayscaled') . "</div><div class=\"value\">" . $sumRain; ?> mm</div></div>
			<div class="bullet">•</div>
            <div class="cell"><div class="txt">Dauer:</div><div class="icon sun"><?php echo Weather::getSVG('sun', 'self_sun_grayscaled') . "</div><div class=\"value\">" . Weather::formatDuration( $sumSunshine ); ?></div></div>
		</div>
<?php 
    if( !$hourlyData )
    {
?>
		<div class="hour">Keine Vorhersagedaten vorhanden</div>
<?php 
    }
    else
    {
        $i=0;
        foreach( $todayValues as $hourlyData ){  
            #$hourlyData['effectiveCloudCoverInOcta'] = 3;//$i;
            #$hourlyData['precipitationProbabilityInPercent'] = 40;
            #$hourlyData['precipitationAmountInMillimeter'] = $i * 0.6;
            #$hourlyData['thunderstormProbabilityInPercent'] = 40;
            $i++;
?>
		<div class="hour">
			<div>
                <div class="time"><div class="from"><?php echo Weather::formatHour($hourlyData['from']) . ' -</div><div class="to">' . Weather::formatHour($hourlyData['to']) ; ?></div></div>
                <div class="sun"><?php echo Weather::convertOctaToSVG($hourlyData['to'],$hourlyData,3,"light");?>
                </div>
                <div class="temperature">
                    <div class="main"><?php echo $hourlyData['airTemperatureInCelsius']; ?></div><div class="sub">°C</div></div>
                <div class="info">
                    <div class="sunshineDuration"><div class="sun"><?php echo Weather::getSVG('sun', 'self_sun_grayscaled') . "</div><div>" . Weather::formatDuration( $hourlyData['sunshineDurationInMinutesSum'] ); ?></div></div>
                    <div class="precipitationProbability"><div><?php echo Weather::getSVG('rain','self_rain_grayscaled') . "</div><div>" . $hourlyData['precipitationProbabilityInPercent']; ?> %</div></div>
                    <div class="precipitationAmount"><?php echo $hourlyData['precipitationAmountInMillimeterSum']; ?> mm</div>
                </div>
                <div class="wind">
                    <div class="compass">
                        <div class="circle"><?php echo Weather::getSVG('compass_circle', 'self_compass_circle_grayscaled'); ?></div>
                        <div class="needle"><?php echo Weather::getSVG('compass_needle', 'self_compass_needle_grayscaled'," style=\"transform: rotate(".( $hourlyData['windDirectionInDegree'] - 180 )."deg);\""); ?></div>
                    </div>
                    <div><?php echo $hourlyData['windSpeedInKilometerPerHour']; ?> km/h</div></div>
            </div>
		</div>
<?php   }
    }?>
    </div>
	<div class="week">
		<div class="title">
            Woche
		</div>
		<div class="summary">
			<div class="cell"><div class="txt">Bereich:</div><div class="icon temperature"><?php echo Weather::getSVG('temperature', 'self_temperature_grayscaled') . "</div><div class=\"value\">" . $minTemperatureWeekly . " °C - " . $maxTemperatureWeekly; ?> °C</div></div>
			<div class="bullet">•</div>
			<div class="cell"><div class="txt">Max.:</div><div class="icon wind"><?php echo Weather::getSVG('wind', 'self_wind_grayscaled') . "</div><div class=\"value\">" . $maxWindSpeedWeekly; ?> km/h</div></div>
			<div class="bullet">•</div>
            <div class="cell"><div class="txt">Sum:</div><div class="icon rain"><?php echo Weather::getSVG('rain', 'self_rain_grayscaled') . "</div><div class=\"value\">" . $sumRainWeekly; ?> mm</div></div>
			<div class="bullet">•</div>
            <div class="cell"><div class="txt">Dauer:</div><div class="icon sun"><?php echo Weather::getSVG('sun', 'self_sun_grayscaled') . "</div><div class=\"value\">" . Weather::formatDuration( $sumSunshineWeekly ); ?></div></div>
		</div>
<?php 
    if( !$hourlyData )
    {
?>
		<div class="hour">Keine Vorhersagedaten vorhanden</div>
<?php 
    }
    else
    {
        foreach( $weekValues as $hourlyData )
        { 
            $clickUrl = $_SERVER['SCRIPT_URL'] . '?date=' . $hourlyData['from']->format("Y-m-d");
            //$hourlyData['effectiveCloudCoverInOcta'] = array_search($hourlyData,$weekValues) * 1.2;
?>
		<div class="hour">
			<div class="mvClickable<?php if( $activeDay->format("Y-m-d") == $hourlyData['from']->format("Y-m-d") ) echo " active"; ?>" mv-url="<?php echo $clickUrl;?>">
                <div class="time"><div class="to"><?php echo Weather::formatWeekdayName($hourlyData['from']) . '</div><div class="from">' . Weather::formatWeekdayDate($hourlyData['to']) ; ?></div></div>
                <div class="sun"><?php echo Weather::convertOctaToSVG($hourlyData['to'],$hourlyData,24,"light");?>
                </div>
                <div class="temperature">
                    <div class="main"><?php echo $hourlyData['airTemperatureInCelsius']; ?></div><div class="sub">°C</div></div>
                <div class="info">
                    <div class="sunshineDuration"><div class="sun"><?php echo Weather::getSVG('sun', 'self_sun_grayscaled') . "</div><div>" . Weather::formatDuration( $hourlyData['sunshineDurationInMinutesSum'] ); ?></div></div>
                    <div class="precipitationProbability"><?php echo Weather::getSVG('rain', 'self_rain_grayscaled') . " " . $hourlyData['precipitationProbabilityInPercent']; ?> %</div>
                    <div class="precipitationAmount"><?php echo $hourlyData['precipitationAmountInMillimeterSum']; ?> mm</div>
                </div>
                <div class="wind">
                    <div class="compass">
                        <div class="circle"><?php echo Weather::getSVG('compass_circle', 'self_compass_circle_grayscaled'); ?></div>
                        <div class="needle"><?php echo Weather::getSVG('compass_needle', 'self_compass_needle_grayscaled'," style=\"transform: rotate(".( $hourlyData['windDirectionInDegree'] - 180 )."deg);\""); ?></div>
                    </div>
                    <div><?php echo $hourlyData['windSpeedInKilometerPerHour']; ?> km/h</div>
                </div>
                <div class="status"></div>
            </div>
		</div>
<?php   }
    }?>
	</div>
</div>
