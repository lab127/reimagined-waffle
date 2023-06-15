1. buat new blank spreadsheet
2. klik extension -> app script
3. buat file script baru, dan beri nama baru
4. kopi paste kode di bawah ini, ganti *foldername* dengan folder yang diinginkan
5. save project
6. run project -> review permission
7. hasil file spreadsheet berada di root google drive dengan nama **ListOfFiles_foldername**

```js
function listFolderContents() {
  var foldername = 'public'; // provide the name of Folder from which you want to get the list of files
  var ListOfFiles = 'ListOfFiles_' + foldername;
  
  var folders = DriveApp.getFoldersByName(foldername)
  var folder = folders.next();
  var contents = folder.getFiles();
  
  var ss = SpreadsheetApp.create(ListOfFiles);
  var sheet = ss.getActiveSheet();
  sheet.appendRow( ['name', 'link','sizeInMB'] );
  
  var var_file;
  var var_name;
  var var_link;
  var var_size;

  while(contents.hasNext()) {
    var_file = contents.next();
    var_name = var_file.getName();
    var_link = var_file.getUrl();
    var_size=var_file.getSize()/1024.0/1024.0;
    sheet.appendRow( [var_name, var_link,var_size] );     
  }  
};
```