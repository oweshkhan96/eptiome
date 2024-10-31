from SmartApi import SmartConnect
import pyotp  


api_key = "zhbW9hWx"
secret_key = "fac4cf78-5efa-45d2-86cc-f58f73a7cf4a"
client_id = "client_id"  
password = "password"      


obj = SmartConnect(api_key=api_key)


totp_secret = "totp_secret"  
try:
    
    totp = pyotp.TOTP(totp_secret).now()

    
    data = obj.generateSession(client_id, password, totp)
    
    
    if 'data' in data:
        access_token = data['data']['jwtToken']
        refresh_token = data['data']['refreshToken']
        print("Logged in successfully.")
        print(f"Access Token: {access_token}")
        print(f"Refresh Token: {refresh_token}")
    else:
        print("Login failed: ", data.get('message', 'No specific error message found.'))

except Exception as e:
    print("Error generating TOTP: ", str(e))
