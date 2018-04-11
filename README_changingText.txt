Editing Text On Pages
=====================
For Content Pages: Overview, FAQ, Upload Requirements, Administrator, Not Authorized and Guide
If you are an administrator, you can change text by the following option
Go to Manage > Content > Content
Follow the name of the Title of the page, on the same row, 
you will find an Edit button on the right side of table under Operations.
By clicking, you can modify the title and body of the page.
Don’t forget to save if you have made any changes to the page.


For Webform Pages: Create Account, Contact
If you are an administrator, you can change text by the following option
Go to Manage > Structure > Webforms 
For changing the title of the form,
Go to Settings > General
Inside the General Settings, you can change the text inside the field of the Title

For changing the names of the elements, there are 2 options:
Go to Build > Elements
Follow the name of the Title of the element, on the same row,
again you will find an Edit button on the right side of the table under Operations.
Make changes there and then save.
Another Option, Go to Build > Source
In this option, you will find a textfields of Elements.
Within the field, simply change the text within ' ',right next to '#title':
It represents the title of the element which will display on the actual form.


For Custom Form Modules Pages: Single Upload, Upload, Search/Home
Go to module_name/src/Form/ModuleNameForm.php
e.g. FileUploadForm.php
     SingleUploadForm.php
     IssnTableForm.php
     ResultsTable.php
Within the method:
public function buildForm(array $form, FormStateInterface $form_state){

/...look for '#title' & t(' ') , those are the indication of displaying text in the form
    It will look something like this 
         $form['element_name'] = [
               '#title' => t('Change text here'),
               '#description' => t('Change more text here'),
         ];
..../
}


