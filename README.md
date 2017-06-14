# roku-auth
authentication for Roku private channel

Process starts with the code uploaded to the roku device (accessed by users on private channel) on git roku-auth-on-device

on roku-auth-on-device: 
source/main.brs calls SimpleScene.brs in lines 19 and 20:
* scene = screen.CreateScene("SimpleScene")
* screen.show()
  
components/SimpleScene.brs calls generate and authenticate with token of Roku deviceID; gets json  
  
generate/index.php does the following: 
* reads the token; 
* generates a 6-digit alphanumeric code for screen display; 
* inserts into db token, code, update_gen, linked; 
* returns as json token; code; and update as boolean

authenticate/index.php does the following: 
* reads the token; 
* generates a 12-digit alphanumric code for authentication;
* writes authentication code to db;
* returns json: token; linked state; auth code
  
SimpleScene displays the code on the screen of the Roku and tells user to enter code and agent ID number at activate/index.html

activate/index.html takes input values code and agent ID number and passes to authenticate/activation.php and does the following:
* sanitizes input;
* checks agent ID: is the agent in the subscriber db? 
* checks agent ID for number of entries. Agents are allowed 2 devices per subscription, so values of 0 and 1 are acceptable
* checks code entered: if in db, linked = yes  
