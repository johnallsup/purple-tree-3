class Ajax {
  constructor(apiroot="/.api") {
    this.apiroot = apiroot
  }

  // Callbacks take a single parameter, the result of the ajax
  store(path,source,callback,failcallback) {
    console.log("store_ajax",path)
    const request = { path, source }
    return this.dispatch("store",request,callback,failcallback)
  }
  source(path,callback,failcallback) {
    console.log("source_ajax",path)
    const request = { path }
    return this.dispatch("source",request,callback,failcallback)
  }
  mtime(path,callback,failcallback) {
    console.log("mtime_ajax",path)
    const request = { path }
    return this.dispatch("mtime",request,callback,failcallback)
  }
  preview(path,source,callback,failcallback) {
    console.log("previewPage ajax",path)
    const request = { path, source }
    console.log(266,{path,request})
    return this.dispatch("preview",request,callback,failcallback)
  }
  versions(path,callback,failcallback) {
    console.log("versions",path)
    const request = { path }
    return this.dispatch("versions",request,callback,failcallback)
  }
  ddir(path,callback,failcallback) {
    const request = { path }
    return this.dispatch("ddir",request,callback,failcallback)
  }
  upload(files,callback,xhr = null,showMessage = true) {
    // callback handles both success, partial and error
    // hopefully we can replace jquery with vanilla as we did for dispatch
    let n = files.length
    let form_data = new FormData()
    for( let file_obj of files ) {
      let filename = file_obj.name
      let xs = filename.split("/").slice(-1)[0].split(".")
      let ext = xs.pop()
      let stub = xs.join(".")
      ext = ext.replace(/[^a-zA-Z0-9_+%@=-]+/g,"_")
      stub = stub.replace(/[^a-zA-Z0-9_+%@=-]+/g,"_")
      filename = stub + "." + ext
      file_obj = new File([file_obj],filename)
      form_data.append('file[]', file_obj)
    }
    form_data.append('location',window.location.href)
    let request = {
      url: "/.api/upload",
      type: "POST",
      data: form_data,
      contentType: false,
      cache: false,
      processData: false,
      beforeSend: _ => {
        console.log(1234,this)
        if( showMessage) {
          window.ptui.infoBox.showContent(`Uploading ${n} files`)
        }
      },
      success: data => {
        try {
          data = JSON.parse(data)
        } catch(e) {
          console.log(`Failed to parse JSON`,data)
          return window.ptui.errorBox.showContent(`Failed to parse JSON`)
        }
        return callback(data)
      },
      error: e => {
        console.log("upload error",e)
        window.ptui.errorBox.showContent(`Upload failed, see console`)
      }
    }
    if( xhr !== null ) {
      request['xhr'] = xhr
    }
    $.ajax(request)
  }
  dispatch(endpoint,request,callback,failcallback) {
    const url = `${this.apiroot}/${endpoint}`
    let json = JSON.stringify(request)
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);

    xhr.setRequestHeader('Content-type', 'application/json');

    xhr.addEventListener("readystatechange",function() {//Call a function when the state changes.
      if(xhr.readyState == 4 && xhr.status == 200) {
        console.log("http 500",xhr.responseText)
      }
      if(xhr.readyState == 4 && xhr.status == 200) {
        let json = xhr.responseText
        try {
          console.log({json})
          let newdata = JSON.parse(json)
          console.log("parsed",newdata)
          return callback(newdata)
        } catch(e) {
          console.log("dispatch error 656",json,e)
          window.errorRequest = request
          window.error = e + ": json=" + json
          return failcallback(e)
        }
      }
    })

    xhr.send(json)
  }
}
