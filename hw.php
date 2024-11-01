<?php 
/*
Plugin Name: Wunderground Historical Data
Plugin URI: http://turneremanager.com
Description: Search historical records data from Wunderground API
Author: Matthew M. Emma and Robert Carmosino
Version: 1.0
Author URI: http://www.turneremanager.com
*/
add_action('wp_loaded', 'init_HistoricalWunderground');

class HistoricalWunderground {

  public function __construct() {
    add_action( 'wp_enqueue_scripts', array($this, 'weatherfont_style'), 10, 0  );
    add_shortcode('hw', array($this, 'wunderground_history'));
  }
  public function weatherfont_style() {
    wp_register_style('weatherfont', plugins_url('/css/weather-icons.css', __FILE__));
    wp_enqueue_style( 'weatherfont' );
  }
  public function wunderground_history( $atts ) {
    extract( shortcode_atts( array(
      'city' => 'New_York',
      'state' => 'NY',
      'y' => '1986',
      'm' => '11',
      'd' => '27',
      'icon' => 72,
      'deg' => 'F'
    ), $atts, 'hw' ) );
    $json_string = file_get_contents('http://api.wunderground.com/api/b8e924a8f008b81e/history_' . $y . $m . $d . '/q/' . $state . '/' . $city . '.json');
    $parsed_json = json_decode($json_string);
    $dailysummary = $parsed_json->{'history'}->{'dailysummary'}[0];
    $observations = $parsed_json->{'history'}->{'observations'};
    $obsarray = array();
    $hourSearch = 6;
    foreach ($observations as $observation) {
      $hour = $observation->{'date'}->{'hour'};
      if ($hour == $hourSearch) {
        array_push($obsarray, $observation);
        $hourSearch += 6;
      }
    }
  
    $weatherunit = '<div class="historicalweather"><div class="wgroup">';
  
    foreach ($obsarray as $obs) {
      $cols = floor(100 / count($obsarray) );
      $weatherunit .= '<div class="time" style="float: left; text-align: center; width: '.$cols.'%">'
      .str_replace(' on ', '<br>', $obs->{'date'}->{'pretty'}).'<br><br>'
      .$this->wunderground_to_history_icon($obs->{'conds'}, $icon)
      .'<br><br>'.$obs->{'conds'}.'</div>';
    }

    if ($deg ==='F'){
      $temperature = 'Low: ' . $dailysummary->{'mintempi'} . '&deg;F, High: ' . $dailysummary->{'maxtempi'} . '&deg;F';
    }

    if ($deg ==='C'){
      $temperature = 'Low: ' . $dailysummary->{'mintempm'} . '&deg;C, High: ' . $dailysummary->{'maxtempm'} . '&deg;C';
    }

    $weatherunit .= '</div><div class="wgroup"><hr>
    <div class="tsv" style="float: left; text-align: center; width: 33.3%"><strong>Temperature</strong><br>'.$temperature .'</div>
    <div class="tsv" style="float: left; text-align: center; width: 33.4%">Avg. Wind Speed: ' . $dailysummary->{'meanwindspdi'} . ' mph</div>
    <div class="tsv" style="float: left; text-align: center; width: 33.3%">Avg. Visibility: ' . $dailysummary->{'meanvisi'} . ' miles</div>
    </div>';
    
    if(strcmp($dailysummary->{'rain'}, "1") == 0) {
      if(strcmp($dailysummary->{'snow'}, "1") == 0) {
        $weatherunit .= '<div class="rainsnow" style="float: left; text-align: center; width: 50%">Rainfall: ' . $dailysummary->{'precipi'} . '</div>
        <div class="rainsnow" style="float: left; text-align: center; width: 50%">Snowfall: ' . $dailysummary->{'snowfalli'} . '</div>';
      } else {
        $weatherunit .= '<div class="rain" style="text-align: center">Rainfall: ' . $dailysummary->{'precipi'} . '</div>';
      }
    } else {
      if(strcmp($dailysummary->{'snow'}, "1") == 0) {
        $weatherunit .= '<div class="snow" style="text-align: center">Snowfall: ' . $dailysummary->{'snowfalli'} . '</div>';
      }
    }
    
    $weatherunit .= '</div>';
    return $weatherunit;
  }

  private function wunderground_to_history_icon( $status, $size ) {
    if (strncmp($status, 'Light', 5) == 0 || strncmp($status, 'Heavy', 5) == 0) {
        $status = substr($status, 6);
    }
    $icons = array(
      'Drizzle' => 'wi-day-sprinkle',
      'Rain' => 'wi-day-rain',
      'Snow' => 'wi-day-snow',
      'Snow Grains' => 'wi-day-snow',
      'Ice Crystals' => 'wi-day-snow',
      'Ice Pellets' => 'wi-day-snow',
      'Hail' => 'wi-day-hail',
      'Mist' => 'wi-day-fog',
      'Fog' => 'wi-day-fog',
      'Fog Patches' => 'wi-day-fog',
      'Smoke' => 'wi-smoke',
      'Volcanic Ash' => 'wi-smog',
      'Widespread Dust' => 'wi-dust',
      'Sand' => 'wi-dust',
      'Haze' => 'wi-smog',
      'Spray' => 'wi-day-sprinkle',
      'Dust Whirls' => 'wi-dust',
      'Sandstorm' => 'wi-tornado',
      'Low Drifting Snow' => 'wi-day-snow',
      'Low Drifting Widespread Dust' => 'wi-dust',
      'Low Drifting Sand' => 'wi-dust',
      'Blowing Snow' => 'wi-day-snow-wind',
      'Blowing Widespread Dust' => 'wi-dust',
      'Blowing Sand' => 'wi-dust',
      'Rain Mist' => 'wi-day-sprinkle',
      'Rain Showers' => 'wi-day-showers',
      'Snow Showers' => 'wi-day-snow',
      'Snow Blowing Snow Mist' => 'wi-day-snow-wind',
      'Ice Pellet Showers' => 'wi-day-hail',
      'Hail Showers' => 'wi-day-hail',
      'Small Hail Showers' => 'wi-day-hail',
      'Thunderstorm' => 'wi-day-thunderstorm',
      'Thunderstorms and Rain' => 'wi-day-storm-showers',
      'Thunderstorms and Snow' => 'wi-day-snow-thunderstorm',
      'Thunderstorms and Ice Pellets' => 'wi-day-snow-thunderstorm',
      'Thunderstorms with Hail' => 'wi-day-snow-thunderstorm',
      'Thunderstorms with Small Hail' => 'wi-day-snow-thunderstorm',
      'Freezing Drizzle' => 'wi-day-rain-mix',
      'Freezing Rain' => 'wi-day-rain-mix',
      'Freezing Fog' => 'wi-day-fog',
      'Patches of Fog' => 'wi-day-fog',
      'Shallow Fog' => 'wi-day-fog',
      'Partial Fog' => 'wi-day-fog',
      'Overcast' => 'wi-day-sunny-overcast',
      'Clear' => 'wi-day-sunny',
      'Partly Cloudy' => 'wi-day-cloudy',
      'Mostly Cloudy' => 'wi-day-cloudy',
      'Scattered Clouds' => 'wi-day-cloudy',
      'Small Hail' => 'wi-day-snow',
      'Squalls' => 'wi-day-cloudy-gusts',
      'Funnel Cloud' => 'wi-tornado',
      'Unknown Precipitation' => 'wi-day-rain',
      'Unknown' => 'wi-day-sunny'
    );
    return '<i style="font-size: '.$size.'px;" class="wi '.$icons[$status].'"></i>';
  }
}

function init_HistoricalWunderground() {
  if(method_exists('HistoricalWunderground', 'wunderground_history')) {
    $WPHistoricalWunderground = new HistoricalWunderground();
  }
}
