#!/usr/bin/env php
<?php

class PHPMinify
{
    const PHPDOC = 'abstract|api|author|bar|category|copyright|deprecated|domain|example'.
        '|experimental|final|filesource|global|foo|forbar|ignore|internal|inheritdoc|inheritDoc'.
        '|license|link|method|override|package|param|private|property|return|see|since|source'.
        '|src|subpackage|test|throws|todo|TODO|uses|var|version';
    protected $stmtStack = array();
    protected $funcStack = array();

    protected $tokens = array();
    protected $newTokens = array();

    protected $mapTokens = array();

    protected $noNewline = false;
    protected $noDocComment = true;
    protected $noEmptyBlock = true;
    protected $minifyMore = false;
    protected $fixLineno = false;

    public function __construct($noNewline = false, $noDocComment = true, $noEmptyBlock = true, $minifyMore = false, $fixLineno)
    {
        $this->noNewline = $noNewline;
        $this->noDocComment = $noDocComment;
        $this->noEmptyBlock = $noEmptyBlock;
        $this->minifyMore = $minifyMore;
        $this->fixLineno = $fixLineno;
    }

    public function minifyDir($path)
    {
        if(!file_exists($path)) {
            echo '// not found: ' . $path . "\n";
            return false;
        }
        if(is_file($path)) {
            return $this->minifyFile($path);
        }
        if(!($dh = opendir($path))) {
            echo '// not open: ' . $path . "\n";
            return false;
        }
        echo '// minify: ' . $path . "\n";
        while (($name = readdir($dh)) !== false) {
            if ($name == '.' || $name == '..') {
                continue;
            }
            $this->minifyDir($path . '/' . $name);
        }
        return true;
    }

    public function minifyFile($name)
    {
        if(!file_exists($name)) {
            echo '// not found: ' . $name . "\n";
            return false;
        }
        if( pathinfo($name, PATHINFO_EXTENSION ) != 'php'){
            echo '// skip: ' . $name . "\n";
            return false;
        }
        echo '// minify: ' . $name . "\n";
        $text = file_get_contents($name);
        $text = $this->minify($text);
        /**
        //print_r($this->tokens);
        //print_r($this->newTokens);
        echo "// +++++++++++++++++++++++++++++++++++\n";
        echo $text;
        echo "// -----------------------------------\n";
        /*/
        return file_put_contents($name, $text);
        //*/
    }

    public function __toString()
    {
        return getCode();
    }

    public function minify($text)
    {
        $this->setCode($text);
        $this->minifyCode();
        if($this->minifyMore) {
            return $this->getCode($this->minifyToken());
        }
        return $this->getCode();
    }

    public function setCode($text)
    {
        $this->tokens = token_get_all($text);
        $this->newTokens = array();
        $this->stmtStack = array();
        $this->funcStack = array();
        $this->mapTokens = array();
    }

    public function getCode($text2 = false)
    {
        $text = '';
        foreach($this->newTokens as $token) {
            if($text2 && isset($token->text2)) {
                $text .= $token->text2 . $token->rpad;
            } else {
                $text .= $token->text . $token->rpad;
            }
        }
        return $text;
    }

    public function minifyCode()
    {
        $stmt=null;
        while($token = $this->getToken()) {
            $stmt2 = $this->topStmt();
            $token2 = $this->topToken();
            switch($token->id) {
            case T_DOC_COMMENT:              //
                if($this->noDocComment) {
                    $token->text = preg_replace('/^\s*\*\s*@(' . self::PHPDOC . ').*$/m', '', $token->text);
                    if(!preg_match('/^\s*\*\s*@[a-zA-Z]/m', $token->text)) {
                        continue 2;
                    }
                    $token->text = preg_replace('/^\s+/m', '', $token->text);
                    $token->text = preg_replace('/^\*\s*\w.*$/m', '', $token->text);
                    $token->text = preg_replace('/\s+$/m', '', $token->text);
                    $token->text = preg_replace('/^\*$/m', '', $token->text);
                    $token->text = preg_replace('/\n+/', "\n", $token->text);
                }
                if(!$this->noNewline) {
                    $token->rpad = "\n";
                }
                if(!empty($token2)) {
                    $token2->rpad = "\n";
                }
                break;
            case T_COMMENT:                  //
            case T_WHITESPACE:               // \t\r\n\x20
                continue 2;                  // ignore white space and comment
            case T_OPEN_TAG:                 // <?php
            case T_OPEN_TAG_WITH_ECHO:       // <?=
            case T_START_HEREDOC:            // <<<EOF
                $this->pushStmt($token->id);
                break;
            case T_CLOSE_TAG:                // ? >
                $this->popStmt();
                if($token2) {
                    if($this->noEmptyBlock && ($token2->id == T_OPEN_TAG || $token2->id == T_OPEN_TAG_WITH_ECHO)) {
                        $this->popToken();
                        continue 2;
                    }
                    if($token2->id == ';' && $stmt2 == T_OPEN_TAG_WITH_ECHO) {
                        $this->popToken();
                    }
                }
                break;
            case T_END_HEREDOC:              // EOF
                $this->popStmt();
                break;
            case T_ELSE:                     // else
            case T_ELSEIF:                   // elseif
            case T_CATCH:                    // catch
            case T_FINALLY:                  // finally
                if($token2 && $token2->text == '}') {
                    $token2->rpad = '';
                }
            case T_DECLARE:                  // declare
            case T_NAMESPACE:                // namespace
            case T_INTERFACE:                // interface
            case T_CLASS:                    // class
            case T_TRAIT:                    // trait
            case T_FUNCTION:                 // function
            case T_FOR:                      // for
            case T_FOREACH:                  // foreach
            case T_IF:                       // if
            case T_TRY:                      // try
            case T_WHILE:                    // while
            case T_DO:                       // do
            case T_SWITCH:                   // switch
                $stmt = $token->id;
                break;
            case T_CURLY_OPEN:               // {
            case T_DOLLAR_OPEN_CURLY_BRACES: // ${
                $this->pushStmt('{');
                break;
            case T_LINE:
                if($this->fixLineno) {
                    $token->text = $token->line;
                }
                break;
            case '{':
                if(empty($stmt)) {
                    $this->pushStmt('{');
                } else {
                    $this->pushStmt($stmt);
                    if(!$this->noNewline) {
                        $token->rpad = "\n";
                    }
                    $stmt = null;
                }
                break;
            case '}':
                if($token2 && $token2->id == '{') {
                    $token2->rpad = '';
                }
                if($stmt2 != '{' && !$this->noNewline) {
                    $token->rpad = "\n";
                }
                $this->popStmt();
                break;
            case ';':
                if($stmt2 == '(') {
                    break;
                }
                $stmt = null;
                if($token2 && $token2->id == ';') {
                    continue 2;
                }
                if($token2 && $token2->id == '}') {
                    $token2->rpad = '';
                }
                if(!$this->noNewline) {
                    $token->rpad = "\n";
                }
                break;
            case '"':
                if($stmt2 == '"') {
                    $this->popStmt();
                } else {
                    $this->pushStmt('"');
                }
                break;
            case '?':
                if($stmt != T_FUNCTION) {
                    $this->pushStmt('?');
                }
                break;
            case ':':
                if($stmt2 == '?') {
                    $this->popStmt();
                }elseif($stmt != T_FUNCTION && !$this->noNewline) {
                    $token->rpad = "\n";
                }
                break;
            case '(':
                $this->pushStmt('(');
                break;
            case ')':
                if($stmt2 == '(') {
                    $this->popStmt();
                }
            case ',':
                if($token2 && $token2->id == '}') {
                    $token2->rpad = '';
                }
                break;
            default:
                break;
            }
            if(!empty($token2)) {
                if($token->sticky && $token->id != T_VARIABLE && $token2->sticky && $token2->id != T_LNUMBER && $token2->id != T_DNUMBER) {
                        $token2->rpad = ' ';
                }
                if($token2->id == '.' && ($token->id == T_DNUMBER || $token->id == T_LNUMBER || ($token->id == T_LINE && $this->fixLineno))) {
                    $token2->rpad = ' ';
                }
                if($token->id == '.' && ($token2->id == T_DNUMBER || $token2->id == T_LNUMBER || ($token2->id == T_LINE && $this->fixLineno))) {
                    $token2->rpad = ' ';
                }
            }
            $this->pushToken($token);
        }
    }

    protected function minifyToken()
    {
        $this->stmtStack = array();

        foreach($this->newTokens as &$token) {
            if($this->topFunc() == T_USE) {
                $this->popFunc();
                continue;
            }

            if($token->id == T_USE) {
                $this->pushFunc($token->id);
                continue;
            }

            if($token->id == T_FUNCTION) {
                $this->pushFunc($token->id);
                continue;
            }
 
            if(empty($this->topFunc())) {
               continue;
            }

            //if($token->id == T_STRING && $this->topFunc() == T_FUNCTION) {
            //    if(substr($token->text, -6) == 'Action') {
            //        $this->pushFunc('Action');
            //    } elseif(substr($token->text, 0, 5) == 'twig_') {
            //        $this->pushFunc('twig');
            //    }
            //    continue;
            //}

            if($token->id == '{' || $token->id == T_CURLY_OPEN || $token->id == T_DOLLAR_OPEN_CURLY_BRACES) {
                //if($this->topFunc() == 'Action' || $this->topFunc() == 'twig') {
                //    $this->popFunc();
                //}
                $this->pushFunc($token->id);
            } elseif($token->id == '}') {
                $this->popFunc();
            }

            if($this->topStmt() == T_DOUBLE_COLON) {
                $this->popStmt();
                //if($token->id == T_DOLLAR_OPEN_CURLY_BRACES) {
                //    $this->pushStmt('{');
                //}
                continue;
            }
            if($token->id == T_DOUBLE_COLON) {
                //echo "$token->name-$token->line\n";
                $this->pushStmt($token->id);
                continue;
            }
            if(empty($this->topStmt())) {
                if($token->id == T_DOLLAR_OPEN_CURLY_BRACES || $token->id == '$') {
                    $this->pushStmt($token->id);
                }elseif($token->id == T_VARIABLE) {
                    $token->text2 = '$' . $this->getNewToken(substr($token->text, 1));
                }
                continue;
            }
            if($this->topStmt() == '[' && $token->id != ']') {
                continue;
            }
            if($token->id == '{') {
                if($this->topStmt() == '$') {
                    $this->pushStmt('${');
                } else {
                    $this->pushStmt('{');
                }
            }elseif($token->id == '}') {
                $this->popStmt();
                if($this->topStmt() == '$') {
                    $this->popStmt();
                }
            }elseif($token->id == ']') {
                $this->popStmt();
            }elseif($token->id == '[') {
                $this->pushStmt($token->id);
            //}elseif($token->id == T_VARIABLE) {
            //    $token->text2 = '$' . $this->getNewToken(substr($token->text, 1));
            }elseif($token->id == T_STRING_VARNAME) {
                $token->text2 = $this->getNewToken($token->text);
            }elseif($token->id == T_CONSTANT_ENCAPSED_STRING) {
                $token->text2 = $this->getNewToken(stripcslashes(substr($token->text, 1, -1)));
                if($this->topStmt() != T_DOLLAR_OPEN_CURLY_BRACES) {
                    $token->text2 = "'" . $token->text2 . "'";
                }
            }elseif($token->id == T_DNUMBER) {
                $token->text2 = $this->getNewToken($token->text + 0);
                if($this->topStmt() != T_DOLLAR_OPEN_CURLY_BRACES) {
                    $token->text2 = "'" . $token->text2 . "'";
                }
            }elseif($token->id == T_LNUMBER) {
                $token->text2 = $this->getNewToken($token->text + 0);
                if($this->topStmt() != T_DOLLAR_OPEN_CURLY_BRACES) {
                    $token->text2 = "'" . $token->text2 . "'";
                }
            }else{
                return false;
            }
        }
        return true;
    }

    protected function getToken()
    {
        $token = current($this->tokens);
        if(empty($token)) {
            return false;
        }
        next($this->tokens);
        $newToken = new StdClass();
        if(is_array($token)) {
            $newToken->id = $token[0];
            $newToken->name = token_name($token[0]);
            $newToken->text = $token[1];
            $newToken->line = $token[2];
        } else {
            $newToken->id = $token;
            $newToken->text = $token;
            $newToken->line = 0;
            $newToken->name = $token;
        }
        $newToken->rpad = '';
        $newToken->sticky = $this->isTokenSticky($newToken);
        return $newToken;
    }

    protected function getNewToken($text)
    {
        $text = (string) $text;
        switch($text) {
        case 'GLOBALS':
        case '_SERVER':
        case '_GET':
        case '_POST':
        case '_FILES':
        case '_REQUEST':
        case '_SESSION':
        case '_ENV':
        case '_COOKIE':
        case 'php_errormsg':
        case 'HTTP_RAW_POST_DATA':
        case 'http_response_header':
        case 'argc':
        case 'argv':
        case 'this':
        //case 'method':
            return $text;
        }
        if(!array_key_exists($text, $this->mapTokens)) {
            //if($this->topFunc() == T_FUNCTION) {
            //    $this->mapTokens[$text] = $text;
            //    return $text;
            //}
            //if($this->topFunc() == 'Action' || $this->topFunc() == 'twig') {
            //    $this->mapTokens[$text] = $text;
            //    return $text;
            //}
            $num = count($this->mapTokens);
            if($num < 26) {
                $this->mapTokens[$text] = chr($num + 97);
            } elseif($num < 702) {
                $this->mapTokens[$text] = chr(floor($num/26-1) + 97) . chr($num%26 + 97);
            } elseif($num < 18278) {
                $this->mapTokens[$text] = chr(floor(($num-26)/676-1) + 97) . chr(floor($num/26-1)%26 + 97) . chr($num%26 + 97);
            } else {
                $this->mapTokens[$text] = 'a' . ($num - 18278);
            }
        }
        return $this->mapTokens[$text];
    }

    protected function pushFunc($func)
    {
        if(empty($func)) {
            return false;
        }
        return array_push($this->funcStack, $func);
    }

    protected function popFunc()
    {
        $func = array_pop($this->funcStack);
        if(empty($this->funcStack)) {
            $this->mapTokens = array();
        }
        return $func;
    }

    protected function topFunc()
    {
        return end($this->funcStack);
    }
    protected function pushToken($token)
    {
        if(empty($token)) {
            return false;
        }
        return array_push($this->newTokens, $token);
    }

    protected function popToken()
    {
        return array_pop($this->newTokens);
    }

    protected function topToken()
    {
        return end($this->newTokens);
    }

    protected function pushStmt($stmt)
    {
        if(empty($stmt)) {
            return false;
        }
        return array_push($this->stmtStack, $stmt);
    }

    protected function popStmt()
    {
        return array_pop($this->stmtStack);
    }

    protected function topStmt()
    {
        return end($this->stmtStack);
    }

    protected function isTokenSticky($token)
    {
        switch($token->id) {
        case T_AND_EQUAL:                // &=
        case T_BOOLEAN_AND:              // &&
        case T_BOOLEAN_OR:               // ||
        case T_COALESCE:                 // ??
        case T_CONCAT_EQUAL:             // .=
        case T_CURLY_OPEN:               // {
        case T_DEC:                      // --
        case T_DIV_EQUAL:                // /=
        case T_DOLLAR_OPEN_CURLY_BRACES: // ${
        case T_DOUBLE_ARROW:             // =>
        case T_DOUBLE_COLON:             // ::
        case T_INC:                      // ++
        case T_IS_EQUAL:                 // ==
        case T_IS_GREATER_OR_EQUAL:      // >=
        case T_IS_IDENTICAL:             // ===
        case T_IS_NOT_EQUAL:             // !=,<>
        case T_IS_NOT_IDENTICAL:         // !==
        case T_IS_SMALLER_OR_EQUAL:      // <=
        case T_MINUS_EQUAL:              // -=
        case T_MOD_EQUAL:                // %=
        case T_MUL_EQUAL:                // *=
        case T_NS_SEPARATOR:             // \ 
        case T_OBJECT_OPERATOR:          // ->
        case T_OR_EQUAL:                 // |=
        case T_PAAMAYIM_NEKUDOTAYIM:     // ::
        case T_PLUS_EQUAL:               // +=
        case T_SL:                       // <<
        case T_SL_EQUAL:                 // <<=
        case T_SPACESHIP:                // <=>
        case T_SR:                       // >>
        case T_SR_EQUAL:                 // >>=
        case T_XOR_EQUAL:                // ^=
        case T_ARRAY_CAST:               // (array)
        case T_BOOL_CAST:                // (bool),(boolean)        
        case T_DOUBLE_CAST:              // (real),(double),(float)
        case T_INT_CAST:                 // (int),(integer)
        case T_OBJECT_CAST:              // (object)
        case T_STRING_CAST:              // (string)
        case T_UNSET_CAST:               // (unset)
        case T_OPEN_TAG:                 // <?php
        case T_OPEN_TAG_WITH_ECHO:       // <?=
        case T_CLOSE_TAG:                // ? >
        case T_DOC_COMMENT:              // /** ... */
        case T_START_HEREDOC:            // <<<EOF
        case T_END_HEREDOC:              // EOF
        case T_CONSTANT_ENCAPSED_STRING:
        case T_ENCAPSED_AND_WHITESPACE:
            return false;
        default:
            return !is_string($token->id);
        }
    }
}

(function($argc, $argv) {
    if($argc <= 0) {
        echo $argv[0] . " filename\n";
        return false;
    }
    array_shift($argv);
    $argc --;
    $nonewline = false;
    $nodoccomment = true;
    $noemptyblock = true;
    $minifymore = false;
    $fixlineno = false;
    for($i = 0; $i < $argc; $i ++) {
        switch($argv[$i]) {
        case '--newline':
        case '--doc-comment':
        case '--empty-block':
            ${'no' . str_replace('-', '', $argv[$i])} = false;
            break;
        case '--fix-lineno':
        case '--minify-more':
        case '--no-doc-comment':
        case '--no-empty-block':
        case '--no-newline':
            ${str_replace('-', '', $argv[$i])} = true;
            break;
        default: continue 2;
        }
        unset($argv[$i]);
    }
    //PHPMinify::__construct($noNewline = false, $noDocComment = true, $noEmptyBlock = true, $minifyMore = false)
    $minify = new PHPMinify($nonewline, $nodoccomment, $noemptyblock, $minifymore, $fixlineno);
    foreach($argv as $arg) {
        $minify->minifyDir($arg);
    }
})($argc, $argv);
