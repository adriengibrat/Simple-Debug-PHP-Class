<?php

class debug {

	static public $on = false;

	/**
	* @var array List of errors types
	*/
	static private $error = array(
		E_ERROR           => 'Fatal error',
		E_WARNING         => 'Warning',
		E_PARSE           => 'Parse error', // won't be used....
		E_NOTICE          => 'Notice',
		E_CORE_ERROR      => 'Fatal error',
		E_CORE_WARNING    => 'Warning',
		E_COMPILE_ERROR   => 'Compile error',
		E_COMPILE_WARNING => 'Compile warning',
		E_USER_ERROR      => 'Fatal error',
		E_USER_WARNING    => 'Warning',
		E_USER_NOTICE     => 'Notice',
		E_STRICT          => 'Notice strict'
	);

	/**
	* @var array overloading methods
	*/
	static private $overload = array(
		'__call' => 2,
		'__callStatic' => 2,
		'__get' => 1,
		'__set' => 1,
		'__clone' => 1,
		'offsetGet' => 1,
		'offsetSet' => 1,
		'offsetUnset' => 1,
		'offsetExists' => 1,
	);

	static private $time;

	static private $chrono;

	static public function handler ( $type, $message, $file, $line, $scope ) {
		global $php_errormsg; // set global error message regardless track errors settings
		$php_errormsg = self::clean_message( $message );
		if ( ! self::$on ) { // de-activate
			return false;
		}
		$stack = self::stack( debug_backtrace( false ), $type ); // clean stack depending if error is user triggered or not
		self::overload( $stack, $file, $line  ); // clean stack + switch line & file if overloaded method triggered the error
		echo '<br />', PHP_EOL, '<b>', self::$error[$type], '</b>: ', $php_errormsg, ' in <b>',
			 $file, '</b> on line <b>', $line, '</b>'; // print error
		if ( $type & (E_ALL^E_NOTICE^E_USER_NOTICE) ) {
			self::context( $stack, $scope ); // print stack trace & context
		}
		echo '<br />', PHP_EOL;
		if ( $type & E_USER_ERROR ) { // fatal error
			exit;
		}
	}

	static protected function stack ( $backtrace, $type ) {
		$user_triggered = E_USER_ERROR|E_USER_WARNING|E_USER_NOTICE;
		return array_slice( $backtrace,  $type & $user_triggered ?  2 : 1 );
	}

	static protected function overload ( &$stack, &$file, &$line ) {
		if ( isset( $stack[0]['class'], self::$overload[$stack[0]['function']] )
		  && $offset = self::$overload[$stack[0]['function']] ) {
			for ( $i = 0; $i < $offset; $i++ ) {
				extract( array_shift( $stack ) ); // clean stack and overwrite file & line
			}
		}
	}

	static protected function clean_message ( $message ) {
		return preg_replace( '~^.*</a>\]: +(?:\([^\)]+\): +)?~', null, $message ); // clean useless infos
	}

	static protected function context ( $stack, $scope ) {
		if ( $stack ) {
			echo '<br />Stack trace:';
			foreach ( $stack as $index => $call ) {
				echo '<br />#', $index, ' ';
				if ( isset( $call['file'] ) ) {
					echo $call['file'], '(', $call['line'], ')';
				} else {
					echo '[internal function]';
				}
				echo ': ', @$call['class'], @$call['type'],
					 $call['function'], '(', self::args_export( @$call['args'] ), ')';
			}
			echo '<br />#', $index + 1, ' {main}';
			if ( $scope && ! isset( $scope['GLOBALS'] ) ) {
				echo '<br /><i>Context</i>:';
				foreach ( $scope as $name => $value ) {
					echo '<br />$', $name,' = ', self::var_export( $value ), ';';
				}
			}
		}
	}

	static protected function var_export ( $var ) {
		if ( is_array( $var ) ) {
			return 'array(' . self::args_export( $var ) . ')';
		}
		$output = var_export( $var, true );
		if ( is_object( $var ) ) {
			return 'object(' . substr( $output, 0, strpos( $output, ':') ) . ')';
		}
		return $output;
	}

	static protected function args_export ( $args ) {
		if ( $args ) { // format arguments if any
			return implode(', ',
				array_map( 'debug::var_export', $args )
			);
		}
	}

	static public function chrono( $print = null, $scope = '' ) {
		if ( ! isset( self::$time[$scope] ) ) {
			echo '<br />', PHP_EOL, '<b>', $scope, ' init', '</b><br />', PHP_EOL;
		} elseif ( is_string( $print ) ) {
			echo '<br />', PHP_EOL,
				sprintf('%s -> %s: %ss',
					$scope,
					$print,
					round( self::$chrono[$scope][$print] = microtime(true) - self::$time[$scope], 6 )
				), '<br />', PHP_EOL;
		} elseif ( $print && isset( self::$chrono[$scope] ) ) {
			asort( self::$chrono[$scope] );
			$base = reset ( self::$chrono[$scope] ); // shortest duration
			foreach( self::$chrono[$scope] as $event => $duration ) {
				$table[] = sprintf('%5s - %-38.38s <i>%7ss</i>',
					round( $duration / $base, 2 ),
					$event,
					round( $duration, 3 )
				);
			}
			echo '<br />', PHP_EOL, '<b>', $scope, ' chrono</b><pre>',
				sprintf('%\'-61s %-46s<i>duration</i>%1$s%1$\'-61s',
					'<br />',
					'unit - action'
				), implode( '<br />', $table ), '</pre>', PHP_EOL;
		}
		return self::$time[$scope] = microtime(true);
	}

}

set_error_handler  ( 'debug::handler', E_ALL );

?>
