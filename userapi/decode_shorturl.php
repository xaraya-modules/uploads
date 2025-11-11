<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\UserApi;

use Xaraya\Modules\Uploads\UserApi;
use Xaraya\Modules\MethodClass;
use Exception;

/**
 * uploads userapi decode_shorturl function
 * @extends MethodClass<UserApi>
 */
class DecodeShorturlMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * extract function and arguments from short URLs for this module, and pass
     * them back to xarGetRequestInfo()
     * @todo fix signature
     * @author the Example module development team
     * @param mixed $params array containing the different elements of the virtual path
     * @return array|void array containing func the function to be called and args the query
     * string arguments, or empty if it failed
     * @see UserApi::decodeShorturl()
     */
    public function __invoke(array $params = [])
    {
        // Initialise the argument list we will return
        $args = [];

        // Analyse the different parts of the virtual path
        // $params[1] contains the first part after index.php/example

        // In general, you should be strict in encoding URLs, but as liberal
        // as possible in trying to decode them...
        if (empty($params[1])) {
            // nothing specified -> we'll go to the main function
            return ['download', $args];
        } elseif (preg_match('/^(\d+)\.(.*)/', $params[1], $matches)) {
            // something that starts with a number must be for the display function
            // Note : make sure your encoding/decoding is consistent ! :-)
            $fileId = $matches[1];

            /** @var UserApi $userapi */
            $userapi = $this->userapi();

            $fileExists = $userapi->dbCount(['fileId' => $fileId]);

            if (!$fileExists) {
                $msg = $this->ml('Unable to display - file \'#(1)\' does not exist!', $params[1]);
                throw new Exception($msg);
            } else {
                $args['fileId'] = $fileId;
                return ['download', $args];
            }
        }
    }
}
