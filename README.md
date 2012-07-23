## How to use it
Easy, just include the class file and it automatically replace the default error & exception handlers!

To log any message / variable / data:
```php
<?php
Debug::log( 'message', $var1, $var2, ... ); // log as much data as you want
Debug::dump( 'message', $var1, $var2, ... ); // log and exit
```

To use the chrono:
```php
<?php
Debug::chrono(); // set the timer
Debug::chrono('your message'); // display your message & time elapsed since last chrono call
Debug::chrono(true); // display a table with all messages & times
```

Use several chrono at the same time by passing chrono name as second argument:
```php
<?php
Debug::chrono( null, 'chrono1' ); // set the timer of chrono 1
Debug::chrono( null, 'chrono2' ); // set the timer of chrono 2
usleep( 5000 );
Debug::chrono( 'your message 1', 'chrono1' ); // display your message & time elapsed since last chrono 1 call
usleep( 5000 );
Debug::chrono( 'your message 2', 'chrono2' ); // display your message & time elapsed since last chrono 2 call

Debug::chrono( true, 'chrono1' ); // display a table with all messages & times of chrono 1
Debug::chrono( true, 'chrono2' ); // display a table with all messages & times of chrono 2
```

There is also (very) short alias functions:
<pre>
	l() === Debug::log();
	d() === Debug::dump();
	c() === Debug::chrono();
</pre>

To turn the display of error, logs and chrono on & off at runtine:
```php
<?php
Debug::reporting( false ); // turn off
// Activate debug and show all error informations (stack trace and context) on given error level
Debug::reporting( E_ALL ); 
```

Debug registers custom error & exception handlers (on file inclusion), to unregister custom handlers:
```php
<?php
Debug::register( false );
// and of course register it again
Debug::register(); 
```

### Error display template:
<pre>
[Error Type]: [Full Error message] in [path and filename] on line xxx
Stack trace:
#0 [path and filename](xxx): [code where error occured]
#1 [path and filename](xxx): [Function call backtrace]
#2 [path and filename](xxx): [Function call backtrace]
#3 {main}
Context:
$var1 = [value];
$var2 = [value];
...
</pre>

### Chrono display template:
<pre>
 chrono init [name]
 -> message 1: x.xxxs
 -> message 2: x.xxxs
 -> message n: x.xxxs
</pre>
<pre>
chrono table [name]
-------------------------------------------------------
 unit - action                                 duration
-------------------------------------------------------
    1 - message 1                                x.xxxs
 x.xx - message 2                                x.xxxs
 x.xx - message n                                x.xxxs
</pre>