<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RouteController extends Controller
{
    public function install(Request $request){
        $payload = array(
            'client_id' => env('ClientId'),
            'client_secret' => env('ClientSecret'),
            'redirect_uri' => env('AppUrl').'/auth/callback',
            'grant_type' => 'authorization_code',
            'code' => $request->get('code'),
            'scope' => $request->get('scope'),
            'context' => $request->get('context'),
        );
        $response = Http::post('https://login.bigcommerce.com/oauth2/token', $payload, [
            'exceptions' => false,
        ]);
    
        if ($response->successful()) {
            $data = $response->json();
            list($context, $storeHash) = explode('/', $data['context'], 2);
            $accessToken = $data['access_token'];
            $storeHash = $data['context'];
            $array = explode('/', $storeHash);
            $storeHash = $array[1];
            $email = $data['user']['email'];

            self::createScript($storeHash, $accessToken, 'app.js');
        } else {
            // Handle non-200 response
            $statusCode = $response->status();
            $errorMessage = $response->body();
            // Handle or log the error appropriately
            Log::error("Error obtaining OAuth2 token - Status Code: $statusCode, Message: $errorMessage");
        }
    }

    public function createScript($storeHash, $accessToken, $file){
        $payload = array(
            'name' => "app.js",
            'description' => "This will be centralised js file in project",
            'src' => env('AppUrl').'/js/'.$file,
            'auto_uninstall' => true, 
            'location' => 'footer',
            'visibility' => 'all_pages',
            'kind' => 'src',
        );
        $response = Http::withHeaders(['X-Auth-Token' => $accessToken])->post('https://api.bigcommerce.com/stores/'.$storeHash.'/v3/content/scripts', $payload, [
            'exceptions' => false
        ]);
    }

    public function load(){
        
    }
}
