#!/usr/bin/env php
<?php

class PHPMinify
{
    protected $stmtStack = array();

    protected $origTokens = array();
    protected $newTokens = array();

    protected $keepNewline = false;
    protected $keepDocComment = false;
    protected $keepEmptyBlock = false;

    public function __construct($keepNewline = false, $keepDocComment = false, $keepEmptyBlock = false)
    {
        $this->keepNewline = $keepNewline;
        $this->keepDocComment = $keepDocComment;
        $this->keepEmptyBlock = $keepEmptyBlock;
    }

    public function minifyDir($path)
    {
        if(!file_exists($path)) {
            echo 'not found: ' . $path . "\n";
            return false;
        }
        if(is_file($path)) {
            return $this->minifyFile($path);
        }
        if(!($dh = opendir($path))) {
            echo 'not open: ' . $path . "\n";
            return false;
        }
        echo 'minify: ' . $path . "\n";
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
            echo 'not found: ' . $name . "\n";
            return false;
        }
        if( pathinfo($name, PATHINFO_EXTENSION ) != 'php'){
            echo 'skip: ' . $name . "\n";
            return false;
        }
        echo 'minify: ' . $name . "\n";
        $text = file_get_contents($name);
        $text = $this->minify($text);
        /**
        echo "+++++++++++++++++++++++++++++++++++\n";
        echo $text;
        echo "-----------------------------------\n";
        /*/
        
        return file_put_contents($name, $text);
        //*/
    }

    public function minify($text)
    {
        $this->origTokens = token_get_all($text);
        $this->newTokens = array();
        $this->stmtStack = array();
        $stmt = null;
        while($token = $this->getToken()) {
            switch($token->id) {
            case T_DOC_COMMENT:              //
                if($this->keepDocComment || preg_match('/ @[A-Z]| @required/', $token->text)) {
                    if($this->keepNewline) {
                        $token->rpad = "\n";
                    }
                    break;
                }
            case T_COMMENT:                  // 
            case T_WHITESPACE:               // \t\r\n\x20
                continue 2;
            case T_OPEN_TAG:                 // <?php
            case T_OPEN_TAG_WITH_ECHO:       // <?=
            case T_START_HEREDOC:            // <<<\EOF
                $this->pushStmt($token->id);
                $this->fixToken($token);
                break;
            case T_CLOSE_TAG:                // ?\>
                $stmt2 = $this->popStmt();
                if($token2 = $this->popToken()) {
                    if($token2->id == T_OPEN_TAG || $token2->id == T_OPEN_TAG_WITH_ECHO) {
                        if(!$this->keepEmptyBlock) {
                            continue 2;
                        }
                    }
                    if($token2->id != -1 || $token2->text != ';' || $stmt2 != T_OPEN_TAG_WITH_ECHO) {
                        $this->pushToken($token2);
                    }
                }
                $this->fixToken($token);
                break;
            case T_END_HEREDOC:              // EOF
                $stmt2 = $this->popStmt();
                $this->fixToken($token);
                break;
            case T_ELSE:                     // else
            case T_ELSEIF:                   // elseif
            case T_CATCH:                    // catch
            case T_FINALLY:                  // finally
                if($token2 = $this->popToken()) {
                    if($token2->id == -1 && $token2->text == '}') {
                        $token2->rpad = '';
                    }
                    $this->pushToken($token2);
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
            case -1:                         // xxx
                if($token->text == '{') {
                    if($stmt != '') {
                        $this->pushStmt($stmt);
                        if($this->keepNewline) {
                            $token->rpad = "\n";
                        }
                        $stmt = '';
                    } else {
                        $this->pushStmt('{');
                    }
                } elseif($token->text == '}') {
                    $stmt2 = $this->popStmt();
                    if($stmt2 != '{' && $this->keepNewline) {
                        $token->rpad = "\n";
                        break;
                    }
                    if($token2 = $this->popToken()) {
                        if($token2->id == -1 && $token2->text == '{') {
                            $token2->rpad = '';
                        }
                        $this->pushToken($token2);
                    }
                } elseif($token->text == ';') {
                    if($stmt != T_FOR) {
                        $stmt = '';
                        if($token2 = $this->popToken()) {
                            if($token2->id == -1) {
                                if($token2->text == ';') {
                                    $this->pushToken($token2);
                                    continue 2;
                                } elseif ($token2->text == '}') {
                                    $token2->rpad = '';
                                }
                            }
                            $this->pushToken($token2);
                        }
                        if($this->keepNewline) {
                            $token->rpad = "\n";
                        }
                    }
                } elseif($token->text == '"') {
                    $stmt2 = $this->popStmt();
                    if($stmt2 != '"') {
                        $this->pushStmt($stmt2);
                        $this->pushStmt('"');
                    }
                } elseif($token->text == '?') {
                    $this->pushStmt('?');
                } elseif($token->text == ':') {
                    $stmt2 = $this->popStmt();
                    if($stmt2 != '?' && $stmt != T_FUNCTION) {
                        $this->pushStmt($stmt2);
                        if($this->keepNewline) {
                            $token->rpad = "\n";
                        }
                    }
                } elseif($token->text == ',' || $token->text == ')') {
                    if($token2 = $this->popToken()) {
                        if($token2->id == -1 && $token2->text == '}') {
                            $token2->rpad = '';
                        }
                        $this->pushToken($token2);
                    }
                }
                break;
            default:
                break;
            }
            if($token->id == T_DOC_COMMENT) {
                if($token2 = $this->popToken()) {
                    $token2->rpad = "\n";
                    $this->pushToken($token2);
                }
            }
            if($token->needpad && $token->id != T_VARIABLE) {
                if($token2 = $this->popToken()) {
                    if($token2->needpad && $token2->id != T_LNUMBER && $token2->id != T_DNUMBER && $token2->rpad == '') {
                        $token2->rpad = ' ';
                    }
                    $this->pushToken($token2);
                }
            }
            $this->pushToken($token);
        }
        return $this->__toString();
    }

    public function __toString()
    {
        $text = '';
        foreach($this->newTokens as $token) {
            $text .= $token->text . $token->rpad;
        }
        return $text;
    }

    protected function getToken()
    {
        $token = current($this->origTokens);
        if(empty($token)) {
            return false;
        }
        next($this->origTokens);
        $newToken = new StdClass();
        if(is_array($token)) {
            $newToken->id = $token[0];
            $newToken->name = token_name($token[0]);
            $newToken->text = $token[1];
            $newToken->line = $token[2];
        } else {
            $newToken->id = -1;
            $newToken->text = $token;
        }
        $newToken->rpad = '';
        $newToken->needpad = $this->isTokenNeedpad($newToken);
        return $newToken;

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

    protected function fixToken($token)
    {
        switch($token->id) {
        case T_CLOSE_TAG:
        case T_OPEN_TAG_WITH_ECHO:
            $token->text = trim($token->text);
            break;
        case T_OPEN_TAG:
            $token->text = trim($token->text) . ($this->keepNewline?"\n":" ");
            break;
        case T_START_HEREDOC:
            $token->text = trim($token->text) . "\n";
            break;
        case T_END_HEREDOC:
            $token->text = "\n" . trim($token->text);
            break;
        case T_DOC_COMMENT:
        default:
            break;
        }
        return $token; 
    }

    protected function isTokenNeedpad($token)
    {
        switch($token->id) {
        case T_NS_SEPARATOR:             // \ 
        case T_AND_EQUAL:                // &=
        case T_BOOLEAN_AND:              // &&
        case T_BOOLEAN_OR:               // ||
        case T_COALESCE:                 // ??
        case T_CONCAT_EQUAL:             // .=
        case T_DEC:                      // --
        case T_DIV_EQUAL:                // /=
        case T_DOLLAR_OPEN_CURLY_BRACES: // ${
        case T_DOUBLE_ARROW:             // =>
        case T_DOUBLE_COLON:             // ::
        case T_INC:                      // ++
        case T_IS_EQUAL:                 // ==
        case T_IS_GREATER_OR_EQUAL:      // >=
        case T_IS_IDENTICAL:             // ===
        case T_IS_NOT_EQUAL:             // != or <>
        case T_IS_NOT_IDENTICAL:         // !==
        case T_IS_SMALLER_OR_EQUAL:      // <=
        case T_MINUS_EQUAL:              // -=
        case T_MOD_EQUAL:                // %=
        case T_MUL_EQUAL:                // *=
        case T_OBJECT_OPERATOR:          // ->
        case T_OR_EQUAL:                 // |=
        case T_PAAMAYIM_NEKUDOTAYIM:     // ::
        case T_PLUS_EQUAL:               // +=
        case T_SL:                       // <<
        case T_SL_EQUAL:                 // <<=
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
        case T_CONSTANT_ENCAPSED_STRING:
        case T_ENCAPSED_AND_WHITESPACE:
        case T_OPEN_TAG:                 // <?php
        case T_OPEN_TAG_WITH_ECHO:       // <?=
        case T_CLOSE_TAG:                // ?\>
        case T_DOC_COMMENT:
        case T_START_HEREDOC:
        case T_END_HEREDOC:
        case -1:
            return false;
        default:
            return true;
        }
    }
}

(function($argv) {
    $argc = count($argv);
    if($argc <= 1) {
        echo $argv[0] . " filename\n";
        return false;
    }
    $minify = new PHPMinify(0, 0, 0);
    for($i = 1; $i < $argc; $i ++) {
        $minify->minifyDir($argv[$i]);
    }
})($argv);
