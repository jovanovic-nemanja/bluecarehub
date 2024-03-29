<?php

namespace App\Http\Controllers\Auth;

use Mail;
use Session;
use App\User;
use App\Role;
use App\RoleUser;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;

use App\Http\Controllers\Frontend\EmailsController;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'gender' => 'required|integer',
            'birthday' => 'required|date',
            'address' => 'required|string',
            'password' => 'string|min:6|confirmed',
            'phone_number' => 'string|max:255',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        if(!@$data['role']) {
            return false;
        }else{
            if($data['role'] == 1) {    //seller
                $role = 2;
            }else if($data['role'] == 2) {    //buyer
                $role = 3;
            }else{
                
            }

            $verify = VerifyEmailcodes::where('email', $data['email'])->first();
            $data['password'] = $verify->password;

            DB::beginTransaction();

            try {
                $user = User::create([
                    'name' => $data['name'],
                    'company_name' => $data['company_name'],
                    'company_logo' => @$data['company_logo'],
                    'email' => $data['email'],
                    'block' => 0,
                    'password' => Hash::make($data['password']),
                    'phone_number' => @$data['phone_number'],
                    'sign_date' => date('Y-m-d h:i:s'),
                ]);

                Image::upload_logo_img($user->id);

                RoleUser::create([
                    'user_id' => $user->id,
                    'role_id' => $role,
                ]);
                DB::commit();


                $controller = new EmailsController;
                $array = [];
                
                $array['username'] = $data['name'];
                $array['receiver_address'] = $data['email'];
                $array['data'] = array('name' => $array['username'], "body" => "Welcome for sign up our site!");
                $array['subject'] = "Successfully sign up your account.";
                $array['sender_address'] = "jovanovic.nemanja.1029@gmail.com";

                $controller->save($array);
                return $user;
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }            
        }
    }

    public function sellerregister() 
    {
        return view('auth.sellerregister');
    }

    public function emailverify() 
    {
        Session::put('role', 'buyer');
        $role = "buyer";
        $email = '';
        return view('auth.emailverify', compact('role', 'email'));
    }

    public function emailverifyseller() 
    {
        Session::put('role', 'seller');
        $role = "seller";
        $email = '';
        return view('auth.emailverify', compact('role', 'email'));
    }

    public function emailverifyforresend($email, $role) 
    {
        $role = $role;

        return view('auth.emailverify', compact('role', 'email'));
    }
}
