//
// Code adapted from https://artisansweb.net/drag-drop-file-upload-using-javascript-php/
//
// rewrite and move to ui
(function() {
  window.addEventListener("load",_ => {
    const { log } = console
    log("Uploader")
    let fileobj
    function upload_file(e) {
        console.log({msg:"upload file"})
        e.preventDefault()
        ajax_file_upload(e.dataTransfer.files)
    }
    function filename_is_image(filename) {
      return filename.match(/\.(jpg|jpeg|jfif|png|webp|gif|svg)$/)
    }

    // TODO use classList and CSS rather than inline styles
    const content_div = q("div.container")
    log("droptargets",qq(".droptarget"))
    log(document.body,q("body"),q("body.droptarget"))
    if( document.body.classList.contains("droptarget") ) {
      const body = document.body
      body.addEventListener("drop", e => {
        console.log("drop")
        e.preventDefault()
        upload_file(e)
        body.classList.remove("dragon")
      })
      body.addEventListener("dragover", e => {
        console.log("dragover")
        body.classList.add("dragon")
        e.preventDefault()
        return false
      })
      body.addEventListener("dragleave", e => {
        console.log("dragleave")
        body.classList.remove("dragon")
        e.preventDefault()
        return false
      })
    }

    const dialog_timeout = 10
    const seconds = 1000

    let dialog_elt = null

    function display_notification(msg,html=false) {
      if( dialog_elt ) {
        dialog_elt.remove()
      }
      dialog_elt = document.createElement("div")
      const elt = dialog_elt
      elt.classList.add("dialog")
      if( html ) elt.innerHTML = msg
      else elt.innerText = msg
      const closeButton = document.createElement("div")
      closeButton.innerText = "Close"
      closeButton.classList.add("close_button")
      closeButton.addEventListener("click", _ => {
        dialog_elt.remove()
      })
      elt.append(closeButton)
      document.body.append(elt)
      setTimeout(_ => { 
        if(!dialog_elt) return
        dialog_elt.remove()
        dialog_elt = null 
      },dialog_timeout*seconds)
    }
    function display_error(error) {
      display_notification(`<span class='upload_error'>Error: ${error}</span>`)
    }
    function on_successful_upload(files, result) {
      console.log({msg:"upload",files,result})
      const successful_files = files.filter(x => x.result === "success" )
      let msg
      if( result === "partial" ) {
        msg = `Partial success: ${successful_files.length}/${files.length} uploaded`
      } else {
        msg = `Success: ${successful_files.length}/${files.length} uploaded`
      }
      const textarea = q("textarea.editor")
      const textareaHasFocus = document.activeElement === textarea;
      if( textareaHasFocus ) {
        const a = textarea.selectionStart;
        const b = textarea.selectionEnd;
        const v = textarea.value
        const vbefore = v.substr(0,a)
        const vsel = v.substr(a,b-a)
        const vafter = v.substr(b)
        let sa, nv
        if( vsel.length == 0 && successful_files.length == 1) {
          const filename = successful_files[0].filename
          const textBefore = `${filename_is_image(filename)?"!":""}[`
          const textAfter = `](${filename})`
          const t = `${textBefore}${vsel}${textAfter}`
          sa = a+textBefore.length
          nv = vbefore+t+vafter
        } else if( successful_files.length >= 1 ) {
          let s = vsel
          let acc = []
          for(let i=0;i<successful_files.length;i++) {
            const filename = successful_files[i].filename
            const textBefore = `${filename_is_image(filename)?"!":""}[`
            const textAfter = `](${filename})`
            const t = `${textBefore}${s}${textAfter}`
            acc.push(t)  
            s = ""
          }
          acc = acc.join("\n")
          sa = a+acc.length
          nv = vbefore+acc+vafter
        }
        textarea.value = nv
        textarea.selectionStart = sa
        textarea.selectionEnd = sa
      }
      const msgarr = []
      for(let i=0; i<files.length; i++) {
        const f = files[i]
        const { filename, result, error } = f
        if( result === "error" ) {
          msgarr.push(`<span class='upload_error'>${filename} &mdash; ${error}</span>`)
        } else {
          const img = filename_is_image(filename) ? ` <img src='${filename}'/>` : ""
          msgarr.push(`<span class='upload_success'>${filename}${img}</span>`)
        }
      }
      msg += "<br/>\n" + msgarr.join("<br/>\n")
      display_notification(msg,true)
    }
    function ajax_file_upload(files_obj) {
      console.log(files_obj)
      if (files_obj != undefined) {
        var form_data = new FormData();
        for (let i = 0; i < files_obj.length; i++) {
          console.log(files_obj)
          form_data.append('file[]', files_obj[i])
        }
        form_data.append('location',window.location.href)
        console.log(`uploading`, files_obj);
        display_notification(`Uploading ${Array.from(files_obj).map(x => x.name).join(", ")}`)
        var xhttp = new XMLHttpRequest();
        xhttp.open("POST", "/.api/upload", true);
        xhttp.onload = function (event) {
          const responseText = this.responseText
          console.log({responseText})
          window.responseText = responseText
          let response 
          try {
            response = JSON.parse(responseText)
            console.log({response})
          } catch (e) {
            console.log({exception:e,message:"Failed to parse JSON",responseText})
            return
          }
          const status = xhttp.status
          if (status == 200) {
            const { result, error, files } = response
            // response will take the form
            /*
            {
              "result": "success|partial|error",
              "error": "error message|null",
              "files": [
                {
                  "filename": "filename",
                  "result": "success",
                  "error": "error message|null"
                },
                ...
              ],
            }
            */
            if (result === "error") {
              display_error(error)
            } else {
              on_successful_upload(files, result)
            }
          } else {
            // this will happen if you don't have write permission at time of upload (a 403)
            display_error(`status ${status}: ${responseText}`)
          }
        }
        xhttp.send(form_data);
      }
    }
  })

})()
