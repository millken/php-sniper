<?
define("MAX_PASSES", 20);
define("MAX_LENGTH", 1024);
/* WORDPRESS LOGIC *****************/
/*
Sets up the wordpress filters and config pages.  
There are two kinds of filters: one for plaintext e-mails and another
for explicit e-mail links.  Naturally, the latter must be run first
(priority 31 by default), or the e-mail detector will corrupt our
links.
*/
//define("ENCODING",'UTF-8');
//mb_internal_encoding(ENCODING); 
//mb_regex_encoding(ENCODING); 
/* enkode($content, $text = NULL, $max_passes = 20, $max_length = 1024)
Encodes a string to be view-time written by obfuscated Javascript.
The max passes parameter is a tight bound on the number of encodings
perormed.  The max length paramater is a loose bound on the length of
the generated Javascript.  Setting it to 0 will use a single pass of
enk_enc_num.
The function works by selecting encodings at random from the array
enkodings, applying them to the given string, and then producing
Javascript to decode.  The Javascript works by recursive evaluation,
which should be nasty enough to stop anything but the most determined
spambots.
The text parameter, if set, overrides the user-settable option
enk_msg.  This is the message overwritten by the JavaScript; if a
browser doesn't support JavaScript, this message will be shown to the
user.
*/
function enkode($content, $text = NULL, $max_passes = MAX_PASSES, $max_length = MAX_LENGTH) {
  global $enkodings, $enk_dec_num;
  /* our base case -- we'll eventually evaluate this code */
  $kode = "document.write(\"" . 
    addcslashes($content,"\\\'\"&\n\r<>") .
    "\");";
  $max_length = max($max_length, strlen($kode) + JS_LEN + 1);
  
  $result = "";
  
  /* build up as many encodings as we can */
  for ($passes = 0;
       $passes < $max_passes && strlen($kode) < $max_length;
       $passes++) {
    /* pick an encoding at random */
    $idx = rand(0, count($enkodings) - 1);
    $enc = $enkodings[$idx][0];
    $dec = $enkodings[$idx][1];
    
    $kode = enkode_pass($kode, $enc, $dec);
  }
  /* mandatory numerical conversion, prevent catching @ signs and
     interpreting neighboring characters as e-mail addresses */
  $kode = enkode_pass($kode, 'enk_enc_num', $enk_dec_num);
  
  return enk_build_js($kode, $text);
}
/* enkode_pass($kode, $enc, $dec)
Encodes a single pass.  $enc is a function pointer and $dec is the Javascript.
*/
function enkode_pass($kode, $enc, $dec) {
  /* first encode */
  $kode = mb_addslashes($enc($kode));
  /* then generate encoded code with decoding afterwards */
  $kode = "kode=\"$kode\";$dec;"; 
  return $kode;
}
/* enk_build_js($kode)
Generates the Javascript recursive evaluator, which is 269 characters
of boilerplate code.
Unfortunately, <noscript> can't be used arbitrarily in XHTML.  A
<span> that we immediately overwrite, serves as an ad hoc <noscript>
tag.
*/
$enkoder_uses = 0;
define('JS_LEN', 269);
function enk_build_js($kode, $text = NULL) {
  global $enkoder_uses;
  $clean = mb_addslashes($kode);
  
  $msg = is_null($text) ? "" : $text;
  
  $name = "enkoder_" . strval($enkoder_uses) . "_" . strval(rand());
  $enkoder_uses += 1;
  $js = <<<EOT
<span id="$name">$msg</span><script type="text/javascript">
/* <!-- */
function hivelogic_$name() {
var kode="$clean";var i,c,x;while(eval(kode));
}
hivelogic_$name();
var span = document.getElementById('$name');
span.parentNode.removeChild(span);
/* --> */
</script>
EOT;
return $js;
}
// from https://gist.github.com/yuya-takeyama/402780
function mb_addslashes($input, $enc = NULL)
{
    if (is_null($enc)) {
        $enc = mb_internal_encoding();
    }
    $len = mb_strlen($input, $enc);
    $result = '';
    for ($i = 0; $i < $len; $i++)
    {
        $char = mb_substr($input, $i, 1, $enc);
        if (strlen($char) === 1) {
            $char = addslashes($char);
        }
        $result .= $char;
    }
    return $result;
}
/* ENCODINGS ***********************/
/* 
   Each encoding should consist of a function and a Javascript string;
   the function performs some scrambling of a string, and the Javascript
   unscrambles that string (assuming that it's stored in a variable
   kode).
*/
/* REVERSE ENCODING */
function enk_enc_reverse($s) {
  $str = strval($s);
  
  $len = mb_strlen($str);
  $o = "";
  for ($i = $len - 1;$i >= 0;$i--) {
    $o .= mb_substr($str,$i,1);
  }
  return $o;  
}
$enk_dec_reverse = <<<EOT
kode=kode.split('').reverse().join('')
EOT;
/* NUM ENCODING (adapted)*/
function enk_enc_num($s) {
  $nums = "";
  // switch to an always 4-byte encoding, to get better numbers out
  // adapted from http://us1.php.net/ord#72463
  $s = mb_convert_encoding($s,"UCS-4BE");
  $len = mb_strlen($s,"UCS-4BE");
  for($i = 0; $i < $len; $i++) { 
    $c = mb_substr($s,$i,1,"UCS-4BE"); 
    $bs = unpack("N",$c);
    $ord = $bs[1];
    $nums .= strval($ord + 3);
    if ($i < $len - 1) { $nums .= ' '; }
  }        
  return $nums;
}
$enk_dec_num = <<<EOT
kode=kode.split(' ');x='';for(i=0;i<kode.length;i++){x+=String.fromCharCode(parseInt(kode[i]-3))}kode=x
EOT;
/* SWAP ENCODING */
function enk_enc_swap($s) {
  $swapped = strval($s);
  $len = mb_strlen($swapped);
  $o = "";
  for ($i = 0;$i < $len - 1;$i += 2) {
    $fst = mb_substr($swapped,$i,1);
    $snd = mb_substr($swapped,$i+1,1);
    $o .= $snd.$fst;
  }
  if ($len % 2 == 1) {
    $o .= mb_substr($swapped,$len-1,1);
  }
  
  return $o;
}
$enk_dec_swap = <<<EOT
x='';for(i=0;i<(kode.length-1);i+=2){x+=kode.charAt(i+1)+kode.charAt(i)}kode=x+(i<kode.length?kode.charAt(kode.length-1):'')
EOT;
function enk_enc_at($s) {
  $str = strval($s);
  
  $len = mb_strlen($str);
  $o = "";
  for ($i = 0;$i < $len;$i++) {
    $c = mb_substr($str,$i,1);
    $o .= ($c == '@' ? '||' : $c.'_');
  }
  return $o;  
}
$enk_dec_at = <<<EOT
x='';for(i=0;i<kode.length;i+=2){if(kode.charAt(i)=='|'&&kode.charAt(i+1)=='|'){x+='@'}else{x+=kode.charAt(i)}}kode=x
EOT;
/* ENCODING LIST *******************/
/*
Listed fully below.  Add in this format to get new phases, which will
be used automatically by the enkode function.
*/
$enkodings = array(
array('enk_enc_reverse',    $enk_dec_reverse),
array('enk_enc_swap',    $enk_dec_swap),
array('enk_enc_num', $enk_dec_num),
array('enk_enc_at',    $enk_dec_at)
);
echo enkode("<a href='aa.h'>test</a>");
?>
