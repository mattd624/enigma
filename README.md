# enigma
Salesforce Outbound Messages to Enigma NMS (www.netsas.com.au). This script adds, deletes, modifies nodes in Enigma according to the information received from Salesforce, using the Enigma REST API.  
- If the IP is blank, it will remove the node. 
- It uses the Opportunity Name as the node name (A-S########). 
- Adds the Salesforce Oppportunity Id into the node description field. 
- Adds a custom Salesforce field called AP_Standard_Name__c to the Enigma node Description field.
- Adds the Opportunity Id to the Enigma node Connection Comment field.
