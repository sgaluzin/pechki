<?php
/**
 * Joomla! System plugin - ScriptMerge
 *
 * @author Yireo (info@yireo.com)
 * @copyright Copyright 2015
 * @license GNU Public License
 * @link http://www.yireo.com
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

// Import the parent class
jimport( 'joomla.plugin.plugin' );

/**
 * ScriptMerge Helper
 */
class ScriptMergeHelper
{
    /**
     * Method to return the output of a JavaScript file
     *
     * @param string $string
     * @return string
     */
    static public function getJsContent($file)
    {
        // Don't try to parse empty (or non-existing) files
        if (empty($file)) return null;
        if (@is_readable($file) == false) return null;

        // Initialize the buffer
        $buffer = @file_get_contents($file);
        if (empty($buffer)) return null;

        // Initialize the basepath
        $basefile = ScriptMergeHelper::getFileUrl($file, false);

        // If compression is enabled
        $application = JFactory::getApplication();
        $compress_js = ScriptMergeHelper::getParams()->get('compress_js');
        if ($application->isSite() && !empty($compress_js)) {

            // JsMinPlus definitely does not work with MooTools (for now)
            if($compress_js == 'jsminplus' && stristr($file, 'mootools') == true) {
                $compress_js = 'simple';
            }

            // Switch between the various compression-schemes
            switch ($compress_js) {

                case 'simple':
                    $buffer = str_replace('/// ', '///', $buffer);		
				    $buffer = str_replace(',//', ', //', $buffer);
    				$buffer = str_replace('{//', '{ //', $buffer);
	    			$buffer = str_replace('}//', '} //', $buffer);
		    		$buffer = str_replace('/**/', '/*  */', $buffer);
			    	$buffer = preg_replace("/\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n/", "", $buffer);
				    $buffer = preg_replace("/\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n/", "", $buffer);
    				$buffer = preg_replace("/\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n/", "", $buffer);
	    			$buffer = preg_replace("/\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n/", "", $buffer);
		    		$buffer = preg_replace("/\/\/.*\n\/\/.*\n\/\/.*\n\/\/.*\n/", "", $buffer);
				    $buffer = preg_replace("/\/\/.*\n\/\/.*\n\/\/.*\n/", "", $buffer);
    				$buffer = preg_replace('/\/\/.*\/\/\n/', '', $buffer);
	    			$buffer = preg_replace("/\s\/\/\".*/", "", $buffer);
		    		$buffer = preg_replace("/\/\/\n/", "\n", $buffer);
			    	$buffer = preg_replace("/\/\/\s.*.\n/", "\n  \n", $buffer);
				    $buffer = preg_replace('/\/\/w[^w].*/', '', $buffer);
    				$buffer = preg_replace('/\/\/s[^s].*/', '', $buffer);
	    			$buffer = preg_replace('/\/\/\*\*\*.*/', '', $buffer);
		    		$buffer = preg_replace('/\/\/\*\s\*\s\*.*/', '', $buffer);
			    	$buffer = preg_replace('!/\*[^\'."].*?\*/!s', '', $buffer);
				    $buffer = preg_replace('/\n\s*\n/', "\n", $buffer);
    				$buffer = preg_replace("/<!--.*-->/Us","", $buffer);
                    break;

                case 'jsminplus':
                    // Compress the js-code
                    $jsMinPhp = JPATH_SITE.'/components/com_scriptmerge/lib/jsminplus.php';
                    if(file_exists($jsMinPhp)) {
                        include_once $jsMinPhp;
                        if(class_exists('JSMinPlus')) {
                            $buffer = JSMinPlus::minify($buffer);
                        }
                    }
                    break;

                case 'closurecompiler':
                    // Compress the js-code through the Google Closure Compiler API
                    $url = 'http://closure-compiler.appspot.com/compile';

                    // Set the POST-variables
                    $post = array(
                        'compilation_level' => 'SIMPLE_OPTIMIZATIONS',
                        'output_format' => 'json',
                        'output_info' => 'compiled_code',
                        'js_code' => urlencode($buffer),
                    );

                    // Initialize CURL
                    $handle = curl_init($url);
                    curl_setopt_array($handle, array(
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_MAXREDIRS => 0,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_CONNECTTIMEOUT => 10,
                        CURLOPT_TIMEOUT => 10,
                    ));
                    curl_setopt($handle, CURLOPT_POST, true);
                    curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($post));

                    // Only proceed with under 200.000 bytes
                    if(strlen($buffer) < 200000) {
                        $data = curl_exec($handle);
                        $json = json_decode($data, true);
                        if(!empty($json['compiledCode'])) {
                            $buffer = $json['compiledCode'];
                        }
                    }
                    break;

                case 'experimental':
                    //START Remove comments.
                    $buffer = str_replace('/// ', '///', $buffer);      
                    $buffer = str_replace(',//', ', //', $buffer);
                    $buffer = str_replace('{//', '{ //', $buffer);
                    $buffer = str_replace('}//', '} //', $buffer);
                    $buffer = str_replace('*//*', '*/  /*', $buffer);
                    $buffer = str_replace('/**/', '/*  */', $buffer);
                    $buffer = str_replace('*///', '*/ //', $buffer);
                    $buffer = preg_replace("/\/\/.*\n\/\/.*\n/", "", $buffer);
                    $buffer = preg_replace("/\s\/\/\".*/", "", $buffer);
                    $buffer = preg_replace("/\/\/\n/", "\n", $buffer);
                    $buffer = preg_replace("/\/\/\s.*.\n/", "\n  \n", $buffer);
                    $buffer = preg_replace('/\/\/w[^w].*/', '', $buffer);
                    $buffer = preg_replace('/\/\/s[^s].*/', '', $buffer);
                    $buffer = preg_replace('/\/\/\*\*\*.*/', '', $buffer);
                    $buffer = preg_replace('/\/\/\*\s\*\s\*.*/', '', $buffer);
                    $buffer = preg_replace('/([^\":\-1\\\.Cb\n])(\/\/)([^,"\';$)ws8E].*)/', '$1$2xxxpp$3', $buffer);
                    do {$buffer = preg_replace('/((\(\s*|\=\s*)[\"\'\\\\]\s*)(([^\(\);]*)xxxpp)/','$1$4', $buffer, 1, $count);} while ($count);
                    $buffer = preg_replace('/([^=:\-1\\\.Cb\n])(\/\/)xxxpp([^,"\';$)ws8].*)/', '$1', $buffer);
                    $buffer = preg_replace("/([^\*])[\/]+\/\*.*[^a-zA-Z0-9\s\-=+\|!@#$%^&()`~\[\]{};:\'\",<.>?]/", "$1", $buffer);
                    $buffer = preg_replace("/\/\*/", "\n/*dddpp", $buffer);
                    $buffer = preg_replace('/((\{\s*|:\s*)[\"\']\s*)(([^\{\};\"\']*)dddpp)/','$1$4', $buffer);
                    $buffer = preg_replace("/\*\//", "xxxpp*/\n", $buffer);
                    $buffer = preg_replace('/((\{\s*|:\s*|\[\s*)[\"\']\s*)(([^\};\"\']*)xxxpp)/','$1$4', $buffer);
                    $buffer = preg_replace('/([\"\'])\s*\/\*/', '$1/*', $buffer);
                    $buffer = preg_replace('/(\n)[^\'"]?\/\*dddpp.*?xxxpp\*\//s', '', $buffer);
                    $buffer = preg_replace('/\n\/\*dddpp([^\s]*)/', '$1', $buffer);
                    $buffer = preg_replace('/xxxpp\*\/\n([^\s]*)/', '*/$1', $buffer);
                    $buffer = preg_replace('/xxxpp\*\/\n([\"])/', '$1', $buffer);
                    $buffer = preg_replace('/(\*)\n*\s*(\/\*)\s*/', '$1$2$3', $buffer);
                    $buffer = preg_replace('/(\*\/)\s*(\")/', '$1$2', $buffer);
                    $buffer = preg_replace('/\/\*dddpp(\s*)/', '/*', $buffer);
                    $buffer = preg_replace('/\n\s*\n/', "\n", $buffer);
                    $buffer = preg_replace("/(<!--.*-->)/Us","$1QQXQQ", $buffer);
                    do {$buffer = preg_replace("/(\(\s*[\"]\s*[^\(\)\"]*<!--.*-->)QQXQQ/Us","$1", $buffer, 1, $count);} while ($count);
                    do {$buffer = preg_replace("/(\(\s*[\']\s*[^\(\)\']*<!--.*-->)QQXQQ/Us","$1", $buffer, 1, $count);} while ($count);
                    $buffer = preg_replace("/(<!--.*-->QQXQQ)/Us","", $buffer);
                    $buffer = preg_replace('/([^\"=:\-1\\\.Cb]\/\/)([^,"*\';$)ws8E].*)/', '$1xpxpp$2', $buffer);
                    do {$buffer = preg_replace('/([^\"\'])(([\(]|[\:]|[\=]|[\+])\s*[\"]\s*)(?!([\'\+]))([^\"\\\\,]*)(xpxpp)/', '$1$2$5', $buffer, 1, $count);} while ($count);
                    do {$buffer = preg_replace('/([^\'])(([\(]|[\:]|[\=]|[\+])\s*[\']\s*)(?!([\"\+]))([^\'\\\\,]*)(xpxpp)/', '$1$2$5', $buffer, 1, $count);} while ($count);
                    do {$buffer = preg_replace('/((\(\s*|\=\s*)[\"\'\\\\]\s*)(([^\(\);,]*)xpxpp)/','$1$4', $buffer, 1, $count);} while ($count);
                    $buffer = preg_replace('/([^=:\-1\\\.Cb])(\/\/)xpxpp([^,"*\';$)ws8].*)/', '$1', $buffer);
                    $buffer = preg_replace('/(^\/\/[^,"*\';$)ws8].*)/', '', $buffer);
                    $buffer = preg_replace('/([^\n\w\-=+\|!@#$%^&*()`~\[\]{};:\'",<.>\/?\\\\])(\/\/)(.*)/', '$1', $buffer);
                    $buffer = preg_replace('/((\R)|([^0]1)|([^\"]\-))(\/\/)(.*)/', '$1', $buffer);
                    //END Remove comments.  
                    //START Remove all whitespaces
                    $buffer = preg_replace('/\s+/', ' ', $buffer);
                    $buffer = preg_replace('/\s*(?:(?=[=\-\+\|%&\*\)\[\]\{\};:\,\.\<\>\!\@\#\^`~]))/', '', $buffer);
                    $buffer = preg_replace('/(?:(?<=[=\-\+\|%&\*\)\[\]\{\};:\,\.\<\>\?\!\@\#\^`~]))\s*/', '', $buffer);
                    $buffer = preg_replace('/([^a-zA-Z0-9\s\-=+\|!@#$%^&*()`~\[\]{};:\'",<.>\/?])\s+([^a-zA-Z0-9\s\-=+\|!@#$%^&*()`~\[\]{};:\'",<.>\/?])/', '$1$2', $buffer);
                    //END Remove all whitespaces
                    break;

                case 0:
                default:
                    break;
            }

            // Make sure the JS-content ends with ;
            $buffer = trim($buffer);
            if(preg_match('/;\$/', $buffer) == false) $buffer .= ';'."\n";

            // Append the filename to the JS-code
            if(ScriptMergeHelper::getParams()->get('use_comments', 1)) {
                $start = "/* [scriptmerge/start] JavaScript file: $basefile */\n\n";
                $end = "/* [scriptmerge/end] JavaScript file: $basefile */\n\n";
                $buffer = $start.$buffer."\n".$end;
            } else {
                $buffer .= "\n";
            }

        // If compression is disabled
        } else {

            // Make sure the JS-content ends with ;
            $buffer = trim($buffer);
            if(preg_match('/;\$/', $buffer) == false) $buffer .= ';'."\n";

            // Remove extra semicolons
            $buffer = preg_replace("/;;\n/", ';', $buffer);

            // Append the filename to the JS-code
            if(ScriptMergeHelper::getParams()->get('use_comments', 1)) {
                $start = "/* [scriptmerge/start] Uncompressed JavaScript file: $basefile */\n\n";
                $end = "/* [scriptmerge/end] Uncompressed JavaScript file: $basefile */\n\n";
                $buffer = $start.$buffer."\n".$end;
            }
        }

        // Detect jQuery
        if(strstr($buffer, 'define("jquery",')) {
            $buffer .= "jQuery.noConflict();\n";
        }

        return $buffer;
    }

    /**
     * Method to clean the final JS
     *
     * @param string $string
     * @return string
     */
    static public function cleanJsContent($buffer)
    {
        return $buffer;
    }


    /**
     * Method to return the output of a CSS file
     *
     * @param string $string
     * @return string
     */
    static public function getCssContent($file)
    {
        // Only inlude a file once
        static $parsed_files = array();
        if(in_array($file, $parsed_files)) {
            return " ";
        }
        $parsed_files[] = $file;

        // Don't try to parse empty (or non-existing) files
        if (empty($file)) return null;
        if (@is_readable($file) == false) return null;

        // Skip files that have already been included
        static $files = array();
        if (in_array($file, $files)) {
            return null;
        } else {
            $files[] = $file;
        }

        // Initialize the buffer
        $buffer = @file_get_contents($file);
        if (empty($buffer)) return null;

        // Create a raw buffer with comments stripped
        $regex = array(
            "`^([\t\s]+)`ism"=>'',
            "`^\/\*(.+?)\*\/`ism"=>"",
            "`([\n\A;]+)\/\*(.+?)\*\/`ism"=>"$1",
            "`([\n\A;\s]+)//(.+?)[\n\r]`ism"=>"$1\n",
            "`(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+`ism"=>"\n"
        );
        $rawBuffer = preg_replace(array_keys($regex), $regex, $buffer);

        // Initialize the basepath
        $basefile = ScriptMergeHelper::getFileUrl($file, false);

        // Follow all @import rules
        $imports = array();
        if (ScriptMergeHelper::getParams()->get('follow_imports', 1) == 1) {
            if (preg_match_all('/@import\ (.*);/i', $rawBuffer, $matches)) {
                foreach ($matches[1] as $index => $match) {

                    // Strip quotes
                    $match = str_replace('url(', '', $match);
                    $match = str_replace('\'', '', $match);
                    $match = str_replace('"', '', $match);
                    $match = str_replace(')', '', $match);
                    $match = trim($match);

                    // Skip URLs and data-URIs
                    if (preg_match('/^(http|https):\/\//', $match)) continue;

                    $importFile = ScriptMergeHelper::getFilePath($match, $file);
                    if (empty($importFile) && strstr($importFile, '/') == false) $importFile = dirname($file).'/'.$match;
                    $importBuffer = ScriptMergeHelper::getCssContent($importFile);
                    $importUrl = ScriptMergeHelper::getFileUrl($importFile, false);

                    if (!empty($importBuffer)) {
                        if(ScriptMergeHelper::getParams()->get('use_comments', 1)) {
                            $buffer .= "\n/* [scriptmerge/notice] CSS import of $importUrl */\n\n".$buffer;
                        }
                        $buffer .= "\n".$importBuffer."\n";
                        $buffer = str_replace($matches[0][$index], "\n", $buffer);
                        $imports[] = $matches[1][$index];

                    } else {
                        $buffer .= "\n/* [scriptmerge/error] CSS import of $importUrl returned empty */\n\n".$buffer;
                    }
                }
            }
        }

        // Replace all relative paths with absolute paths
        if (preg_match_all('/url\(([^\(]+)\)/i', $rawBuffer, $url_matches)) {
            foreach ($url_matches[1] as $url_index => $url_match) {

                // Strip quotes
                $url_match = str_replace('\'', '', $url_match);
                $url_match = str_replace('"', '', $url_match);

                // Skip CSS-stylesheets which need to be followed differently anyway
                if (strstr($url_match, '.css')) continue;

                // Skip URLs and data-URIs
                if (preg_match('/^(http|https):\/\//', $url_match)) continue;
                if (preg_match('/^\/\//', $url_match)) continue;
                if (preg_match('/^data\:/', $url_match)) continue;

                // Normalize this path
                $url_match_path = ScriptMergeHelper::getFilePath($url_match, $file);
                if (empty($url_match_path) && strstr($url_match, '/') == false) $url_match_path = dirname($file).'/'.$url_match;
                if (!empty($url_match_path)) $url_match = ScriptMergeHelper::getFileUrl($url_match_path);
    
                // Replace image URLs
                $imageContent = ScriptMergeHelper::getImageUrl($url_match_path);
                if (!empty($imageContent)) {
                    $url_match = $imageContent;
                }

                $buffer = str_replace($url_matches[0][$url_index], 'url('.$url_match.')', $buffer);
            }
        }

        // Detect PNG-images and try to replace them with WebP-images
        if (preg_match_all('/([a-zA-Z0-9\-\_\/]+)\.(png|jpg|jpeg)/i', $rawBuffer, $matches)) {
            foreach ($matches[0] as $index => $image) {
                $webp = ScriptMergeHelper::getWebpImage($image);
                if ($webp != false && !empty($webp)) {
                    $buffer = str_replace($image, $webp, $buffer);
                } 
            }
        }

        // Move all @import-lines to the top of the CSS-file
        $regexp = '/@import (.*);/i';
        if (preg_match_all($regexp, $rawBuffer, $matches)) {
            $buffer = preg_replace($regexp, '', $buffer);
            $matches[0] = array_unique($matches[0]);
            foreach($matches[0] as $index => $match) {
                if(in_array($matches[1][$index], $imports)) {
                    unset($matches[0][$index]);
                }
            }
            $buffer = implode("\n", $matches[0])."\n".$buffer;
        }

        // If compression is enabled
        $compress_css = ScriptMergeHelper::getParams()->get('compress_css', 0);
        if ($compress_css > 0) {

            switch ($compress_css) {

                case 1: 
                    $buffer = preg_replace('#[\r\n\t\s]+//[^\n\r]+#', ' ', $buffer);
                    $buffer = preg_replace('/[\r\n\t\s]+/s', ' ', $buffer);
                    $buffer = preg_replace('#/\*.*?\*/#', '', $buffer);
                    $buffer = preg_replace('/[\s]*([\{\},;:])[\s]*/', '\1', $buffer);
                    $buffer = preg_replace('/^\s+/', '', $buffer);
                    $buffer .= "\n";
                    break;

                case 2:
                    // Compress the CSS-code
                    $cssMin = JPATH_SITE.'/components/com_scriptmerge/lib/cssmin.php';
                    if(file_exists($cssMin)) include_once $cssMin;
                    if(class_exists('CssMin')) {
                        $buffer = CssMin::minify($buffer);
                    }
                    break;

                case 0:
                default:
                    break;
            }

        // If compression is disabled
        } else { 

            // Append the filename to the CSS-code
            if(ScriptMergeHelper::getParams()->get('use_comments', 1)) {
                $start = "/* [scriptmerge/start] CSS-stylesheet: $basefile */\n\n";
                $end = "/* [scriptmerge/end] CSS-stylesheet: $basefile */\n\n";
                $buffer = $start.$buffer."\n".$end;
            }
        }

        return $buffer;
    }

    /**
     * Method to clean the final CSS
     *
     * @param string $string
     * @return string
     */
    static public function cleanCssContent($buffer)
    {
        // Move all @import-lines to the top of the CSS-file
        $regexp = '/@import[^;]+;/i';
        if (preg_match_all($regexp, $buffer, $matches)) {
            $buffer = preg_replace($regexp, '', $buffer);
            $buffer = implode("\n", $matches[0])."\n".$buffer;
        }

        return $buffer;
    }

    /**
     * Method to return the WebP-equivalent of an image, if possible
     *
     * @param string $string
     * @return string
     */
    static public function getWebpImage($imageUrl)
    {
        // Check if WebP support is enabled
        if (ScriptMergeHelper::getParams()->get('use_webp', 0) == 0) {
            return false;
        }

        // Check for WebP support
        $webp_support = false;

        // Check for the "webp" cookie
        if (isset($_COOKIE['webp']) && $_COOKIE['webp'] == 1) {
            $webp_support = true;

        // Check for Chrome 9 or higher
        } else if (preg_match('/Chrome\/([0-9]+)/', $_SERVER['HTTP_USER_AGENT'], $match) && $match[1] > 8) {
            $webp_support = true;
        }

        if ($webp_support == false) {
            return false;
        }

        // Check for the cwebp binary
        $cwebp = ScriptMergeHelper::getParams()->get('cwebp', '/usr/local/bin/cwebp');
        if (empty($cwebp) || file_exists($cwebp) == false) return false;
        if (function_exists('exec') == false) return false;

        if (preg_match('/^(http|https):\/\//', $imageUrl) && strstr($imageUrl, JURI::root())) {
            $imageUrl = str_replace(JURI::root(), '', $imageUrl);
        }

        $imagePath = JPATH_ROOT.'/'.$imageUrl;
        if (file_exists($imagePath) && @is_file($imagePath)) {

            // Detect alpha-transparency in PNG-images and skip it
            if (preg_match('/\.png$/', $imagePath)) {
                $imageContents = @file_get_contents($imagePath);
                $colorType = ord(@file_get_contents($imagePath, NULL, NULL, 25, 1));
                if ($colorType == 6 || $colorType == 4) {
                    return false;
                } else if (stripos($imageContents, 'PLTE') !== false && stripos($imageContents, 'tRNS') !== false) {
                    return false;
                }
            }

            $webpPath = preg_replace('/\.(png|jpg|jpeg|gif)$/', '.webp', $imagePath);

            if (@is_file($webpPath) == false) {
                $cmd = "$cwebp -q 100 $imagePath -o $webpPath";
                exec($cmd);
            }

            if (@is_file($webpPath)) {
                $webpUrl = str_replace(JPATH_ROOT, '', $webpPath);
                $webpUrl = preg_replace('/^\//', '', $webpUrl);
                $webpUrl = preg_replace('/^\//', '', $webpUrl);
                $webpUrl = JURI::root().$webpUrl;
                return $webpUrl;
            }
        }

        return false;
    }

    /**
     * Method to translate an image into data URI
     *
     * @param string $url
     * @return string
     */
    static public function getImageUrl($file = null)
    {
        // If this is not a file, do not continue
        if (file_exists($file) == false || @is_readable($file) == false) {
            return null;
        }

        // If this is not an image, do not continue
        if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file) == false) {
            return null;
        }

        $image_domain = ScriptMergeHelper::getParams()->get('image_domain');
        if(!empty($image_domain)) {
            $image_url_replace = true;
            if(preg_match('/^(http|https)\:\/\//', $image_domain, $image_domain_match)) {
                if($image_domain_match[1] == 'http' && JURI::getInstance()->isSSL()) {
                    $image_url_replace = false;
                }
            }
            if($image_url_replace == true) {
                $image_url = str_replace(JPATH_SITE, $image_domain, $file);
                return $image_url;
            }
        }

        // Disable further processing
        if (ScriptMergeHelper::getParams()->get('data_uris', 0) == 0) {
            return ScriptMergeHelper::getFileUrl($file);
        }

        // Check the file-length
        if (filesize($file) > ScriptMergeHelper::getParams()->get('data_uris_filesize', 2000)) {
            return null;
        }

        // Fetch the content
        $content = @file_get_contents($file);
        if (empty($content)) {
            return null;
        }

        $mimetype = null; 
        if (preg_match('/\.gif$/i', $file)) {
            $mimetype = 'image/gif';
        } else if (preg_match('/\.png$/i', $file)) {
            $mimetype = 'image/png';
        } else if (preg_match('/\.webp$/i', $file)) {
            $mimetype = 'image/webp';
        } else if (preg_match('/\.(jpg|jpeg)$/i', $file)) {
            $mimetype = 'image/jpg';
        }

        if (!empty($content) && !empty($mimetype)) {
            return 'data:'.$mimetype.';base64,'.base64_encode($content);
        }

        return null;
    }

    /**
     * Check if the cache has expired
     *
     * @param string $cache
     * @return null
     */
    static public function hasExpired($timestampFile, $cacheFile)
    {
        // Check for browser request
        if(isset($_SERVER['HTTP_CACHE_CONTROL']) && $_SERVER['HTTP_CACHE_CONTROL'] == 'no-cache') {
            return true;
        }

        // Check if the expiration file exists
        if (file_exists($timestampFile) && @is_file($timestampFile)) {
            $time = (int)@file_get_contents($timestampFile);
            if ($time < time()) {
                jimport( 'joomla.filesystem.file' );
                JFile::delete($timestampFile);
                JFile::delete($cacheFile);
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * Set a new cache expiration
     *
     * @param string $cache
     * @return null
     */
    private function setCacheExpire($file)
    {
        $config = JFactory::getConfig();
        if(method_exists($config, 'getValue')) {
            $lifetime = (int)$config->getValue('config.lifetime');
        } else {
            $lifetime = (int)$config->get('config.lifetime');
        }
        if (empty($lifetime) || $lifetime < 120) $lifetime = 120;
        $time = time() + $lifetime;
        jimport( 'joomla.filesystem.file' );
        JFile::write($file, $time);
    }

    /**
     * Get a valid file URL
     *
     * @param string $path
     * @return string
     */
    static public function getFileUrl($path, $include_url = true)
    {
        $path = str_replace(JPATH_SITE.'/', '', $path);

        if ($include_url) {
            $path = JURI::root().$path;
        }

        if(JURI::getInstance()->isSSL()) {
            $path = str_replace('http://', 'https://', $path);
        } else {
            $path = str_replace('https://', 'http://', $path);
        }

        return $path;
    }

    /**
     * realpath() replacement
     *
     * @param string $file
     * @param string $base_path
     * @return string
     */
    static public function realpath($file)
    {
        // Return the file (Windows differently than Linux)
        if (DIRECTORY_SEPARATOR == '\\') {
            return $file;
        }
        return realpath($file);
    }

    /**
     * Get a valid filename
     *
     * @param string $file
     * @param string $base_path
     * @return string
     */
    static public function getFilePath($file, $base_path = null)
    {
        // If this begins with a data URI, skip it
        if (preg_match('/^data\:/', $file)) return null;

        // Strip any URL parameter from this
        $file = preg_replace('/\?(.*)/', '', $file);

        // If this is already a correct path, return it
        if (@is_file($file) && @is_readable($file)) {
            return ScriptMergeHelper::realpath($file);
        }

        // Strip the base-URL from this path
        $file = str_replace(JURI::root(), '', $file);

        // Determine the application path
        $app = JRequest::getInt('app', JFactory::getApplication()->getClientId());
        if ($app == 1) {
            $app_path = JPATH_ADMINISTRATOR;
        } else {
            $app_path = JPATH_SITE;
        }

        // Make sure the basepath is not a file
        if (@is_file($base_path)) {
            $base_path = dirname($base_path);
        }

        // Determine the basepath
        if (empty($base_path)) {
            if (substr($file, 0, 1) == '/') {
                $base_path = JPATH_SITE;
            } else {
                $base_path = $app_path;
            }
        }

        // Append the root
        if(@is_file(JPATH_SITE.'/'.$file)) {
            return ScriptMergeHelper::realpath(JPATH_SITE.'/'.$file);
        }

        // Append the base_path
        if (strstr($file, $base_path) == false && !empty($base_path)) {
            $file = $base_path.'/'.$file;
            if(@is_file($file)) {
                return ScriptMergeHelper::realpath($file);
            }
        }

        // Detect the right application-path
        if (JFactory::getApplication()->isAdmin()) {
            if (strstr($file, JPATH_ADMINISTRATOR) == false && @is_file(JPATH_ADMINISTRATOR.'/'.$file)) {
                $file = JPATH_ADMINISTRATOR.'/'.$file;
            } else if (strstr($file, JPATH_SITE) == false && @is_file(JPATH_SITE.'/'.$file)) {
                $file = JPATH_SITE.'/'.$file;
            }
        } else {
            if (strstr($file, JPATH_SITE) == false && @is_file(JPATH_SITE.'/'.$file)) {
                $file = JPATH_SITE.'/'.$file;
            }
        }

        // If this is not a file, return empty
        if (@is_file($file) == false || @is_readable($file) == false) {
            return null;
        }

        return ScriptMergeHelper::realpath($file);
    }

    /**
     * Encode the file-list
     *
     * @param array $files
     * @return string
     */
    static public function encodeList($files)
    {
        $files = implode(',', $files);
        $files = str_replace(JPATH_ADMINISTRATOR.'/', '$B', $files);
        $files = str_replace(JPATH_SITE.'/', '$F', $files);
        $files = str_replace('template', '$T', $files);
        $files = str_replace('js', '$J', $files);
        $files = str_replace('media', '$M', $files);
        $files = str_replace('css', '$C', $files);
        $files = str_replace('system', '$S', $files);
        $files = str_replace('layout', '$l', $files);
        $files = str_replace('cache', '$c', $files);
        $files = str_replace('font', '$f', $files);
        $files = str_replace('tools', '$t', $files);
        $files = str_replace('widgetkit', '$w', $files);
        $files = base64_encode($files);
        return $files;
    }

    /**
     * Decode the file-list
     *
     * @param string $files
     * @return array
     */
    static public function decodeList($files)
    {
        $files = base64_decode($files);
        $files = str_replace('$F', JPATH_SITE.'/', $files);
        $files = str_replace('$B', JPATH_ADMINISTRATOR.'/', $files);
        $files = str_replace('$T', 'template', $files);
        $files = str_replace('$J', 'js', $files);
        $files = str_replace('$M', 'media', $files);
        $files = str_replace('$C', 'css', $files);
        $files = str_replace('$S', 'system', $files);
        $files = str_replace('$l', 'layout', $files);
        $files = str_replace('$c', 'cache', $files);
        $files = str_replace('$f', 'font', $files);
        $files = str_replace('$t', 'tools', $files);
        $files = str_replace('$w', 'widgetkit', $files);
        $files = explode(',', $files);
        return $files;
    }

    /**
     * Send HTTP headers
     *
     * @param string $files
     * @return array
     */
    static public function sendHttpHeaders($buffer, $params, $gzip = false)
    {
        // Send the content-type header
        $type = JRequest::getString('type');
        if ($type == 'css') {
            header('Content-Type: text/css');
        } else {
            header('Content-Type: application/javascript');
        }

        // Construct the expiration time
        $expires = (int)($params->get('expiration', 30) * 60);

        // Set the expiry in the future
        if ($expires > 0) {
            header('Cache-Control: public, max-age='.$expires);
            header('Expires: '.gmdate('D, d M Y H:i:s', time() + $expires));

        // Set the expiry in the past
        } else {
            header("Cache-Control: no-cache, no-store, must-revalidate");
            header('Expires: '.gmdate('D, d M Y H:i:s', time() - (60 * 60 * 24)));
        }

        header('Vary: Accept-Encoding');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()));
        header('ETag: '.md5($buffer));

        if ($gzip == true) {
            header('Content-Encoding: gzip');
        }
    }

    /**
     * Load the parameters
     *
     * @param null
     * @return JParameter
     */
    static public function getParams()
    {
        $plugin = JPluginHelper::getPlugin('system', 'scriptmerge');

        JLoader::import( 'joomla.version' );
        $version = new JVersion();
        if (version_compare( $version->RELEASE, '1.5', 'eq')) {
            jimport('joomla.html.parameter');
            $params = new JParameter($plugin->params);
        } else {
            $params = new JRegistry($plugin->params);
        }

        return $params;
    }
}
