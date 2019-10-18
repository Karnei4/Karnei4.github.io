$(document).ready(function(){
	
 $(document).on('change', '#file_xmlicon', function(){
  var name = document.getElementById("file_xmlicon").files[0].name;
  var form_data = new FormData();
  var ext = name.split('.').pop().toLowerCase();
  if(jQuery.inArray(ext, ['png','jpg','jpeg']) == -1) 
  {
   alert("Invalid Image File! Only .png, .jpg, .jpeg");
  }
  var oFReader = new FileReader();
  oFReader.readAsDataURL(document.getElementById("file_xmlicon").files[0]);
  var f = document.getElementById("file_xmlicon").files[0];
  var fsize = f.size||f.fileSize;
  if(fsize > 2000000)
  {
   alert("Image File Size is very big");
  }
  else
  {
   form_data.append("file", document.getElementById('file_xmlicon').files[0]);
   $.ajax({
    url:"/?do=/plugin&id=userlink&mode=addurl&upload=icon",
    method:"POST",
    data: form_data,
    contentType: false,
    cache: false,
    processData: false,
    beforeSend:function(){
    
    },   
    success:function(data)
    {
     $('#payd_icon').val(data);
	 $('#img_icon').attr("src",data);
	 $('#img_icon').show();
	 listch();
    }
   });
  }
 });
 $(document).on('change', '#file_xml', function(){
  var name = document.getElementById("file_xml").files[0].name;
  var form_data = new FormData();
  var ext = name.split('.').pop().toLowerCase();
  if(jQuery.inArray(ext, ['xml','fxml','m3u','m3u8']) == -1) 
  {
   alert("Invalid File! Only .xml, .fxml, .m3u, .m3u8");
  }
  var oFReader = new FileReader();
  oFReader.readAsDataURL(document.getElementById("file_xml").files[0]);
  var f = document.getElementById("file_xml").files[0];
  var fsize = f.size||f.fileSize;
  if(fsize > 2000000)
  {
   alert("File Size large 2000000bytes");
  }
  else
  {
   form_data.append("file", document.getElementById('file_xml').files[0]);
   $.ajax({
    url:"/?do=/plugin&id=userlink&mode=addurl&upload=xml",
    method:"POST",
    data: form_data,
    contentType: false,
    cache: false,
    processData: false,
    beforeSend:function(){
    
    },   
    success:function(data)
    {
     $('#payd_url').val(data);
	listch();
    }
   });
  }
 }); 
 
 
});