<?php
defined('E_DEPRECATED') || define('E_DEPRECATED', 8192);
defined('E_USER_DEPRECATED') || define('E_USER_DEPRECATED', 16384);
class Debug {
	static protected $reporting = E_ALL;
	static public    function reporting ( $reporting = null ) {
		if ( ! func_num_args() )
			return self::$reporting;
		self::$reporting = $reporting;
	}
	static public    function log () {
    $args = func_get_args();
		self::$reporting && 
			print( 
				self::style() . PHP_EOL . '<pre class="debug log">'
				. implode( 
					'</pre>' . PHP_EOL . '<pre class="log">' 
					, array_map( 'Debug::var_export', $args )
				)
				. '</pre>'
			);
	}
	static public    function dump () {
    $args = func_get_args();
		self::$reporting && 
			die( call_user_func_array( 'Debug::log', $args ) );
	}
	static private   $time;
	static private   $chrono;
	static public    function chrono ( $print = null, $scope = '' ) {
		if ( ! self::$reporting )
			return;
		if ( ! isset( self::$time[ $scope ] ) )
			$chrono [] = '<b class="init">' . $scope . ' chrono init</b>';
		elseif ( is_string( $print ) ) {
			$chrono[] = sprintf('<span class="time">%s -> %s: %fs</span>'
				, $scope
				, $print
				, round( self::$chrono[ $scope ][ $print ] = microtime( true ) - self::$time[ $scope ], 6 )
			);
		} elseif ( $print && isset( self::$chrono[ $scope ] ) ) {
			asort( self::$chrono[ $scope ] );
			$base = reset ( self::$chrono[ $scope ] ); // shortest duration
			foreach( self::$chrono[ $scope ] as $event => $duration )
				$table[] = sprintf( '%5u - %-38.38s <i>%7fs</i>'
					, round( $duration / $base, 2 )
					, $event
					, round( $duration, 3 )
				);
			$chrono[] = '<div class="table"><b>' . $scope . ' chrono table</b>' . PHP_EOL .
				sprintf( '%\'-61s %-46s<i>duration</i>%1$s%1$\'-61s'
					, PHP_EOL
					, 'unit - action'
				) . 
				implode( PHP_EOL, $table ) . PHP_EOL . 
				'</div>';
		}
		echo self::style(), PHP_EOL, '<pre class="debug chrono">', implode( PHP_EOL, $chrono ), '</pre>';
		return self::$time[ $scope ] = microtime( true );
	}
	static private   $registered;
	static public    function register ( $init = true ) {
		if ( $init) {
			if ( ! self::$registered )
				self::$registered = array(
					'display_errors'    => ini_get( 'display_errors' )
          , 'error_reporting' => error_reporting()
					, 'shutdown'        => register_shutdown_function( 'Debug::shutdown' )
				);
			self::$registered[ 'shutdown' ] = true;
			error_reporting( E_ALL );
			set_error_handler( 'Debug::handler', E_ALL );
			set_exception_handler( 'Debug::exception' );
			ini_set( 'display_errors', 0 );
		} elseif ( self::$registered ) {
			self::$registered[ 'shutdown' ] = false;
			error_reporting( self::$registered[ 'error_reporting' ] );
			restore_error_handler();
			restore_exception_handler();
			ini_set( 'display_errors', self::$registered[ 'display_errors' ] );
		}
	}
	static protected $error     = array(
		-1                    => 'Exception'
		, E_ERROR             => 'Fatal'
		, E_RECOVERABLE_ERROR => 'Recoverable'
		, E_WARNING           => 'Warning'
		, E_PARSE             => 'Parse'
		, E_NOTICE            => 'Notice'
		, E_STRICT            => 'Strict'
		, E_DEPRECATED        => 'Deprecated'
		, E_CORE_ERROR        => 'Fatal'
		, E_CORE_WARNING      => 'Warning'
		, E_COMPILE_ERROR     => 'Compile Fatal'
		, E_COMPILE_WARNING   => 'Compile Warning'
		, E_USER_ERROR        => 'Fatal'
		, E_USER_WARNING      => 'Warning'
		, E_USER_NOTICE       => 'Notice'
		, E_USER_DEPRECATED   => 'Deprecated'
	);
	static public    function handler ( $type, $message, $file, $line, $scope, $stack = null ) {
		global $php_errormsg; // set global error message regardless track errors settings
		$php_errormsg = preg_replace( '~^.*</a>\]: +(?:\([^\)]+\): +)?~', null, $message ); // clean useless infos
		if ( ! self::$reporting ) // de-activate
			return false;
		$stack = $stack ? $stack : array_slice( debug_backtrace( false ),  $type & E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE ?  2 : 1 ); // clean stack depending if error is user triggered or not
		self::overload( $stack, $file, $line  ); // switch line & file if overloaded method triggered the error
		echo self::style(), PHP_EOL, '<pre class="debug error ', strtolower( self::$error[ $type ] ), '">', PHP_EOL, 
			sprintf( '<b>%s</b>: %s in <b>%s</b> on line <b>%s</b>'  // print error
				, self::$error[ $type ] ? self::$error[ $type ] : 'Error'
				, $php_errormsg
				, $file
				, $line
			);
		if ( $type & self::$reporting ) // print context
			echo self::context( $stack, $scope );
		echo '</pre>';
		if ( $type & E_USER_ERROR ) // fatal
			exit;
	}
	static public    function shutdown () {
		if ( self::$registered[ 'shutdown' ] && ( $error = error_get_last() ) && ( $error[ 'type' ] & ( E_ERROR |  E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR ) ) )
			self::handler( $error[ 'type' ], $error[ 'message' ], $error[ 'file' ], $error[ 'line' ], null );
	}
	static public    function exception ( Exception $exception ) {
		$msg = sprintf( '"%s" with message "%s"', get_class( $exception ), $exception->getMessage() );
		self::handler( -1, $msg, $exception->getFile(), $exception->getLine(), null, $exception->getTrace() );
	}
	static public    $style     = array(
		'debug'         => 'font-size:1em;padding:.5em;border-radius:5px'
		, 'error'       => 'background:#eee'
		, 'exception'   => 'color:#825'
		, 'parse'       => 'color:#F07'
		, 'compile'     => 'color:#F70'
		, 'fatal'       => 'color:#F00'
		, 'recoverable' => 'color:#F22'
		, 'warning'     => 'color:#E44'
		, 'notice'      => 'color:#E66'
		, 'deprecated'  => 'color:#F88'
		, 'strict'      => 'color:#FAA'
		, 'stack'       => 'padding:.2em .8em;color:#444'
		, 'trace'       => 'border-left:1px solid #ccc;padding-left:1em'
		, 'scope'       => 'padding:.2em .8em;color:#666'
		, 'var'         => 'border-bottom:1px dashed #aaa;margin-top:-.5em;padding-bottom:.9em'
    , 'log'         => 'background:#f7f7f7;color:#33e'
		, 'chrono'      => 'border-left:2px solid #ccc'
		, 'init'        => 'color:#4A6'
		, 'time'        => 'color:#284'
		, 'table'       => 'color:#042'
	);
	static protected function style () {
		static $style;
		if ( $style )
			return;
		foreach ( self::$style as $class => $css )
			$style .= sprintf( '.%s{%s}', $class, $css );
		return PHP_EOL . '<style type="text/css">' . $style . '</style>';
	}
	static protected $overload  = array(
		'__callStatic'   => 2
		, '__call'       => 2
		, '__get'        => 1
		, '__set'        => 1
		, '__clone'      => 1
		, 'offsetGet'    => 1
		, 'offsetSet'    => 1
		, 'offsetUnset'  => 1
		, 'offsetExists' => 1
	);
	static protected function overload ( &$stack, &$file, &$line ) {
		if ( isset( $stack[ 0 ][ 'class' ], self::$overload[ $stack[ 0 ][ 'function' ] ] ) && $offset = self::$overload[ $stack[ 0 ][ 'function' ] ] )
			for ( $i = 0; $i < $offset; $i++ )
				extract( array_shift( $stack ) ); // clean stack and overwrite file & line
	}
	static protected function context ( $stack, $scope ) {
		if ( ! $stack )
				return;
		$context[] = PHP_EOL . '<div class="stack"><i>Stack trace</i> :';
		foreach ( $stack as $index => $call )
			$context[] = sprintf( '  <span class="trace">#%s %s: <b>%s%s%s</b>(%s)</span>'
				, $index
				, isset( $call[ 'file' ] )  ? $call[ 'file' ] . ' (' . $call[ 'line' ] . ')' : '[internal function]'
				, isset( $call[ 'class' ] ) ? $call[ 'class' ]                              : ''
				, isset( $call[ 'type' ] )  ? $call[ 'type' ]                               : ''
				, $call[ 'function' ]
				, isset( $call[ 'args' ] )  ? self::args_export( $call[ 'args' ] )          : ''
			);
		$context[] = '  <span class="trace">#' . ( $index + 1 ) . ' {main}</span>'; 
		$context[] = '</div><div class="scope"><i>Scope</i> :';
    $vars = '';
		if ( isset( $scope['GLOBALS'] ) )
			$vars = '  GLOBAL';
		elseif ( ! $scope )
			$vars = '  NONE';
		else
			foreach ( (array) $scope as $name => $value )
				$vars .= '  <div class="var">$' . $name .' = ' . self::var_export( $value ) . ';' . PHP_EOL . '</div>';
		$context[] = $vars . '</div>';
		return implode( PHP_EOL, $context );
	}
	static protected function var_export ( $var ) {
    ob_start();
    var_dump( $var );
    $export = ob_get_clean();
    $export = preg_replace( '/\s*\bNULL\b/m', ' null', $export ); // Cleanup NULL
    $export = preg_replace( '/\s*\bbool\((true|false)\)/m', ' $1', $export ); // Cleanup booleans
    $export = preg_replace( '/\s*\bint\((\d+)\)/m', ' $1', $export ); // Cleanup integers
    $export = preg_replace( '/\s*\bfloat\(([\d.e-]+)\)/mi', ' $1', $export ); // Cleanup floats
    $export = preg_replace( '/\s*\bstring\(\d+\) /m', '', $export ); // Cleanup strings
    $export = preg_replace( '/object\((\w+)\)(#\d+) \(\d+\)/m', '$1$2', $export ); // Cleanup objects definition
    //@todo array cleaning
    $export = preg_replace( '/=>\s*/m', '=> ', $export ); // No new line between array/object keys and properties
    $export = preg_replace( '/\[([\w": ]+)\]/', ', $1 ', $export ); // remove square brackets in array/object keys
    $export = preg_replace( '/([{(]\s+), /', '$1  ', $export ); // remove first coma in array/object properties listing
    $export = preg_replace( '/\{\s+\}/m', '{}', $export );
    $export = preg_replace( '/\s+$/m', '', $export ); // Trim end spaces/new line
    return $export;
	}
	static protected function simple_export ( $var ) {
    $export = self::var_export( $var );
		if ( is_array( $var ) ) {
      $export  = preg_replace( '/\s+\d+ => /m', ', ', $export );
      $export  = preg_replace( '/\s+(["\w]+ => )/m', ', $1', $export );
      $pattern = '#array\(\d+\) \{[\s,]*([^{}]+|(?R))*?\s+\}#m';
      while ( preg_match( $pattern, $export ) )
        $export  = preg_replace( $pattern, 'array($1)', $export );
      return $export;
    }
		if ( is_object( $var ) )
			return substr( $export, 0, strpos( $export, '#' ) ); // strstr( $export, '#', true );
		return $export;
	}
	static protected function args_export ( $args ) {
		return implode(', ', array_map( 
			'Debug::simple_export', 
			(array) $args
		) );
	}
}
Debug::register();
if ( ! function_exists( 'l' ) ) {
  function l () {
    $args = func_get_args();
    call_user_func_array( 'Debug::log', $args );
  }
}
if ( ! function_exists( 'd' ) ) {
  function d () {
    $args = func_get_args();
  	call_user_func_array( 'Debug::dump', $args );
  }
}
if ( ! function_exists( 'c' ) ) {
  function c () {
    $args = func_get_args();
  	call_user_func_array( 'Debug::chrono', $args );
  }
}