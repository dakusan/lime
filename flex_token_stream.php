<?php
/**
 * Let's face it: PHP is not up to lexical processing. GNU flex handles
 * it well, so I've created a little protocol for delegating the work.
 * Extend this class so that executable() gives a path to your lexical
 * analyser program.
 */
abstract class flex_scanner {
	abstract function executable();

	public function __construct($path) {
		if (!is_readable($path)) {
			throw new Exception("$path is not readable.");
		}

		$scanner = $this->executable();
		$Proc=proc_open($scanner, Array(Array("pipe", "r"), Array("pipe", "w")), $Pipes);
		if(!is_resource($Proc))
			throw new Exception("$scanner execution failed");
		fwrite($Pipes[0], file_get_contents($path));
		fclose($Pipes[0]);
		$tokens = explode("\0", stream_get_contents($Pipes[1]));
		fclose($Pipes[1]);
		proc_close($Proc);

		array_pop($tokens);
		$this->tokens = $tokens;
		$this->lineno = 1;
	}

	public function next() {
		if (list($key, $token) = each($this->tokens)) {
			list($this->lineno, $type, $text) = explode("\1", $token);

			return array($type, $text);
		}
	}

	public function feed($parser) {
		while (list($type, $text) = $this->next()) {
			$parser->eat($type, $text);
		}

		return $parser->eat_eof();
	}
}
