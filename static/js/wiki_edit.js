// TODO -- if we want to make auth stuff optional, we have scope issues.
// we want a namespace, say winodw.PT where we store everything
// then we have window.PT = new PT(); window.addEventListener("load",PT.load())
// then PT adds a new class PT.editor_auth(this) which
// then adds shortcuts via PT.add_shortcut("C-s",)

// shortcuts are defined in the setupKeys() method

class PTUIEdit extends PTUI {
  constructor(ajax) {
    super()
    this.ajax = ajax
    this.editor = new PTEditor(this,q("textarea.editor"))
    this.dirty = false
    window.baseTitle = document.title
    window.addEventListener("focus",e => this.handleFocus(e))
    window.addEventListener("blur", e => document.body.classList.add("blur"))
    window.addEventListener("beforeunload", e => {
      if( this.dirty ) {
        console.log("this.dirty")
        e.preventDefault()
        e.returnValue = ""
        return
      }
      if( window.codeMirrorStarted ) {
        if( window.editor.getValue() !== window.lastSavedSource ) {
          console.log("code mirror diff")
          e.preventDefault()
          e.returnValue = ""
          return
        }
      }
      delete e['returnValue']
    })
  }
  handleFocus(e) {
    document.body.classList.remove("blur")
    this.editor.focus()
  }
  setupUI() {
    super.setupUI()
    const { q, qq } = window
    function c(sel,cb) {
      qq(sel).forEach(elt => {
        elt.addEventListener("click",e => {
          e.preventDefault()
          cb(e)
        })
      })
    }
    // TOOLBAR ACTIONS
    c(".action.duplicate", e => this.duplicateView())
    c(".action.duplicate-edit",e => this.duplicateEdit())
    c(".action.versions",e => this.showVersions() )
    c(".action.abort", e => this.returnToView())
    c(".action.show-preview", e => this.showPreview())
    c(".action.show-goto-box", e => this.gotoBox.show())
    c(".action.save", e => this.save(false))
    c(".action.hamburger", e => this.toggleOptionsBar("hamburger"))
    c(".action.more-options", e => this.toggleOptionsBar("more-options"))
    c(".action.editor-fixquotes",e => this.editor.fixquotes())
    //c(".action.editor-codemirror",e => { window.startCodeMirror() })
    c(".action.editor-normal-font",e => this.editor.setFontSize("normal"))
    c(".action.editor-large-font",e => this.editor.setFontSize("large"))
    c(".action.editor-huge-font",e => this.editor.setFontSize("huge"))
    c(".action.mo-leftarrow", e => this.editor.moveLeft())
    c(".action.mo-rightarrow", e => this.editor.moveRight())
    c(".action.mo-prevheader", e => this.editor.movePrevHeader())
    c(".action.mo-nextheader", e => this.editor.moveNextHeader())
    c(".action.mo-prevline", e => this.editor.prevLine())
    c(".action.mo-nextline", e => this.editor.nextLine())
  }
  showPreview() {
    let { local: path } = this.getUriInfo()
    let source = this.editor.source()
    this.ajax.preview(path,source,e => this.didGetPreview(e),e => this.failedToGetPreview(e))
  }
  didGetPreview(e) {
    console.log("Preview",e)
    let { rendered: htmlSource } = e
    let div = document.createElement("div")
    div.classList.add("rendered-preview")
    div.innerHTML = htmlSource
    this.previewBox.showPreview(window.pageName,window.pagePath,div)
  }
  failedToGetPreview(e) {
    this.errorBox.showContent("Failed to get preview")
    console.log("1234 Failed to get preview",e)
  }
  showVersions() {
    // show in a dialog
    let { local: path } = this.getUriInfo()
    console.log("showVersions",{path})
    this.ajax.versions(path,e => this.didGetVersions(e),e => this.failedToGetVersions(e))
  }
  didGetVersions(e) {
    console.log("Got versions",e)
    let path = e.path.replace(/\.ptmd$/,"")
    let pagename = path.replace(/^.*\//,"")
    this.versionsBox.showVersions(pagename,path,e.versions)
    //this.infoBox.showContent(div)
  }
  failedToGetVersions(e) {
    console.log("Failed to get versions 183",e)
    let div = document.createElement("div")
    let h1 = document.createElement("h1")
    h1.textContent = "Failed to get versions"
    div.append(h1)
    let pre = document.createElement("pre")
    pre.textContent = e.responseText
    div.append(pre)
    this.errorBox.showContent(div)
  }
  setDirty() {
    this.dirty = true
    document.title = "*" + window.baseTitle
    document.body.classList.add("dirty")
  }
  clearDirty() {
    this.dirty = false
    document.title = window.baseTitle
    document.body.classList.remove("dirty")
  }
  unimplemented(e) {
    console.warn("Unimplemented",e)
  }
  toggleOptionsBar(barName) {
    console.log("toggle options",barName)
    let currentOptionsBar = document.body.getAttribute("options-bar")
    if( currentOptionsBar === barName ) {
      document.body.removeAttribute("options-bar")
    } else {
      document.body.setAttribute("options-bar",barName)
    }
  }
  returnToView() {
    let { pagename } = this.getUriInfo()
    window.location.href = pagename
  }
  setupKeys() {
    super.setupKeys()
    const f = (t,d,h) => this.keys.addfp(t,d,h)
    const n = (t,d,h) => this.keys.addnp(t,d,h)

    // KEYBOARD SHORTCUTS
    f("C-`","save and return to view",e => this.save(true))
    f("C-s","save", e => this.save(false))
    f("C-S-a","abort edit and return to view", e => this.returnToView())
    f("C-3","toggle textarea-only", e => document.body.classList.toggle("textarea-only"))
    f("C-p","show preview",e => this.showPreview())
    f("C-S-~","focus editor",e => q("textarea.editor").focus())
    /*
    f("A-S-m","start codemirror",e => {
      console.log("Starting Codemirror")
      if( window.startCodeMirror && !window.codeMirrorStarted ) {
        window.codeMirrorStarted = true
        window.startCodeMirror()
      }
    })
    */
  }
  save(returnToView=false) {
    let source
    if( !window.codeMirrorStarted ) {
      source = this.editor.source()
      console.log("Ordinary textarea")
    } else {
      source = window.editor.getValue()
      console.log("CodeMirror")
    }
    let uriInfo = this.getUriInfo()
    let { local:path } = uriInfo
    this.ajax.store(path, source, e => this.didSave(e,returnToView), e => this.failedToSave(e))
  }
  failedToSave(e) {
    console.log(`Failed to save`,e)
    const { responseText } = e
    let content = document.createElement("div")
    let h1 = document.createElement("h1")
    h1.textContent = "Failed to Save"
    content.append(h1)
    let pre = document.createElement("pre")
    pre.textContent = responseText
    content.append(pre)
    this.errorBox.showContent(content)
  }
  didSave(e,returnToView=false) {
    let source
    if( !window.codeMirrorStarted ) {
      source = this.editor.source()
    } else {
      source = window.editor.getValue()
    }
    window.lastSavedSource = source
    const { mtime } = e
    window.page_mtime = mtime
    const { message, mtime_fmt_short } = e
    console.log("didSave: Save success",{mtime_fmt_short,message,e})
    qq("header .time").forEach(x => {
      x.textContent = mtime_fmt_short
    })

      /*
    let content = document.createElement("div")
    let h1 = document.createElement("h1")
    h1.textContent = "Saved Successfully"
    content.append(h1)
    let pre = document.createElement("pre")
    pre.textContent = responseText
    content.append(pre)
    this.infoBox.showContent(content)
    */
    this.clearDirty()
    if( returnToView ) {
      this.returnToView()
    }
  }
}

class PTEditor {
  constructor(ui,elt,options = {}) {
    this.ui = ui
    this.elt = elt
    this.options = {
      tabStr: "    ",
      ...options
    }
    if( ! this.elt ) throw new Error("No editor element")
    this.keys = new KeyHandler()
    this.elt.addEventListener("keydown",e => this.handleKey(e))
    this.elt.addEventListener("input",e => this.ui.setDirty())
    this.setupEditor()
    this.setupKeys()
    window.lastSavedSource = elt.value
    //this.elt.addEventListener("keyup", () => scrollCurrentLineToMiddle(this.elt));
  }
  handleKey(event) {
    return this.keys.handle(event)
  }
  source() {
    return this.elt.value
  }
  focus(e) {
    setTimeout(_ => this.elt.focus(),10)
  }
  setupEditor() {
    /*
     * garish colours to see if this function was executed.
     */
    //this.elt.style.border = "5px solid yellow"
    //this.elt.style.backgroundColor = "black"
    //this.elt.style.color = "white"
  }
  updateAndShowSideDir() {
    //console.log(this)
    const ajax = window.ptui.ajax
    //console.log("hello123",ajax)
    const urlinfo = window.ptui.getUriInfo()
    const { subdir } = urlinfo
    //console.log("get ddir",{subdir,urlinfo})
    ajax.ddir(subdir,e => {
      //console.log(556,e)
      window.dir = e
      window.ptui.dirSidebar.update()
      this.showSide("dir")
    },e => {
      console.log("ddir error",e)
      this.errorBox.showContent("Failed to get ddir")
    })
  }
  setupKeys() {
    const f = (t,d,h) => this.keys.addfp(t,d,h)
    const fn = (t,d,h) => this.keys.addf(t,d,h)
    const n = (t,d,h) => this.keys.addnp(t,d,h)

    // It is shiftleft/shiftright's job to sort out the selection
    // We want to count the number of characters added or removed by shifting
    // this can be done by comparing the before and after strings
    f("tab","insert spaces",e => {
      let ta = q("textarea.editor")
      if( ! ta ) return
      return this.shiftRight(ta)
    })
    f("S-tab","shift left spaces",e => {
      let ta = q("textarea.editor")
      if( ! ta ) return
      return this.shiftLeft(ta)
    })
    f("C-b","bullet lines",e => {
      let ta = q("textarea.editor")
      if( ! ta ) return
      return this.shiftRight(ta,"* ")
    })
    f("C-e","enumerate lines",e => {
      let ta = q("textarea.editor")
      if( ! ta ) return
      return this.shiftRight(ta,"1. ")
    })
    f("C-enter","start new line below",e => this.insertNewLineBelow())
    f("C-d","show hide directory in sidebar", e => this.updateAndShowSideDir())
    f('C-S-2',"tab size = 2",e => {
      this.options.tabStr = "  "
      let s = "&nbsp;".repeat(2)
      this.ui.infoBox.showHtml(`Tab string now "<code>${s}</code>" (${this.options.tabStr.length} chars)`,500)
    })
    f('C-4',"set size to normal",e => this.setFontSize("normal"))
    f('C-5',"set size to normal",e => this.setFontSize("large"))
    f('C-6',"set size to normal",e => this.setFontSize("huge"))
    f('C-S-3',"tab size = 3",e => {
      this.options.tabStr = "  "
      let s = "&nbsp;".repeat(3)
      this.ui.infoBox.showHtml(`Tab string now "<code>${s}</code>" (${this.options.tabStr.length} chars)`,500)
    })
    f('C-S-4',"tab size = 4",e => {
      this.options.tabStr = "    "
      let s = "&nbsp;".repeat(4)
      this.ui.infoBox.showHtml(`Tab string now "<code>${s}</code>" (${this.options.tabStr.length} chars)`,500)
    })

    // key: $ is cursor pos, # is paste, s is selection
    //      $(...) means ... is the new selection
    // C-S-l [$](s)
    // A-C-l [[s]]$
    // C-A-v Paste link: sel!="" => s [[#]]$ ; else [[#]]$
    // C-S-v Paste link: sel1="" => [s](#)$ ; else [$](#)
    fn("C-S->","insert current date in dd/mm/yyyy format",e => {
      e.preventDefault()
      const today = new Date();
      const yyyy = today.getFullYear();
      let mm = today.getMonth() + 1; // Months start at 0!
      let dd = today.getDate();

      //if (dd < 10) dd = '0' + dd;
      //if (mm < 10) mm = '0' + mm;

      const formattedToday = dd + '/' + mm + '/' + yyyy;
      this.insertAtCursor(formattedToday,false)
    })
    fn("C-y","paste embedded video (e.g. youtube) link [[youtube:id]]",e => this.pasteVideoLink(e))
    fn("C-S-v","paste link []()",e => this.pasteLink1(e))
    fn("A-C-v","paste link [[]]",e => this.pasteLink2(e))
    fn("A-S-v","paste link plain",e => this.pasteLinkPlain(e))
    f("C-/","insert date and bullet",e => {
      const today = (new Date()).toLocaleDateString("en-GB", { weekday: 'long', month: 'short', day: 'numeric' })
      const t = `* **${today}**: `
      this.insertAtCursor(t,true)
    })
    f("C-S-?","insert date",e => {
      const today = (new Date()).toLocaleDateString("en-GB", { weekday: 'long', month: 'short', day: 'numeric' })
      const t = `${today}`
      this.insertAtCursor(t,false)
    })
    f("C-S-l","linkify selection []()",e => this.linkifySelection1())
    f("A-C-l","linkify selection [[]]",e => this.linkifySelection2())
    f("C-space","skip to end of link",e => this.skipToEndOfLink())
    f("C-S-space","skip to start of link",e => this.skipBackToStartOfLink())
    f("A-C-arrowright","shift text right", e => this.shiftRight(q("textarea.editor")))
    f("A-C-arrowleft","shift text left", e => this.shiftLeft(q("textarea.editor")))
    f("C-m","blur focus centre", e => { 
      let t = q("textarea.editor")
      console.log("hello",t) 
      if( t ) { 
        let a = t.selectionStart
        let b = t.selectionEnd
        t.selectionStart = t.selectionEnd = a
        t.blur()
        t.focus() 
        t.selectionStart = a
        t.selectionEnd = b
      } 
    })
    f("A-S-e","edit wikiword",e => this.editWikiWord())
    f("A-S-w","view wikiword",e => this.viewWikiWord())
    f("A-S-p","wikipedia search",e => this.searchWikipedia())
    // we want editor to compile its help, and the help compiler
    // for the ui will take editor's help and prepend it to the main.

    // skip forward -- links headers blocks paras
    // skip backward -- links headers blocks paras
    // consider having a skip mode. So we enter skip mode, and then
    // have all keys available. Thus we need to have a non-default keys,
    // and a common keys. Esc exits skip mode. When in skip mode, change
    // the background-colour of the textarea
    // 
    // skip backwards to header:
    // i = selectionStart
    // left = text.substr(0,i)
    // j = left.lastIndexOf("\n#")
    // pos = j+1 // this will also work if index returns -1
    // selectionStart = selectionEnd = pos
    //
    // skip forwards to header
    // i = selectionEnd
    // right = text.substr(i)
    // j = right.indexOf("^#")
    // if( j == -1 ) j = text.length
    // selectionStart = selectionEnd = j
    //
    // note that this will find lines beginning with # in code blocks.
    // a problem with python

  }
  findContainingWikiWord() {
    const elt = this.elt
    const text = elt.value
    let pos = elt.selectionStart
    while(pos > 0) {
      if( ! text[pos-1].match(/[a-zA-Z0-9_]/) ) {
        break
      }
      pos -= 1 
    }
    let end = pos+1
    while(end + 1 < text.length) {
      if( ! text[end+1].match(/[a-zA-Z0-9_]/) ) {
        break
      }
      end += 1
    }
    let word = text.substring(pos,end+1)
    return word
    // allow any word to be followed
    if( word.match(/[A-Z][A-Za-z0-9_]*[A-Z][A-Za-z0-9_]*/) &&
      word.match(/[a-z0-9_]/) ) {
      return word
    } else {
      return false
    }
  }
  findContainingWord() {
    const elt = this.elt
    const text = elt.value
    let pos = elt.selectionStart
    while(pos > 0) {
      if( ! text[pos-1].match(/\w/) ) {
        break
      }
      pos -= 1 
    }
    let end = pos+1
    while(end + 1 < text.length) {
      if( ! text[end+1].match(/\w/) ) {
        break
      }
      end += 1
    }
    let word = text.substring(pos,end+1)
    if( word == "" ) {
      return false
    }
    return word
  }
  searchWikipedia() {
    const elt = this.elt
    const text = elt.value
    let a = elt.selectionStart
    let b = elt.selectionEnd
    let t
    if( a != b ) {
      t = text.substring(a,b)
    } else {
      t = this.findContainingWord()
      if( t === false ) { 
        return this.ui.errorBox.showContent("No Selection or word")
      }
    }
    t = encodeURIComponent(t)
    let url = `http://en.wikipedia.org/wiki/Special:Search?search=${t}`
    window.open(url,"_blank")
  }
  viewWikiWord() {
    const word = this.findContainingWikiWord()
    if( word !== false ) {
      console.log("edit wikiword",word)
      window.open(`${word}`,"_blank")
    } else {
      console.log("no wikiword")
    }
  }
  editWikiWord() {
    const word = this.findContainingWikiWord()
    if( word !== false ) {
      console.log("edit wikiword",word)
      window.open(`${word}?action=edit`,"_blank")
    } else {
      console.log("no wikiword")
    }
  }
  showSide(what) {
    let a = document.body.getAttribute("show-side")
    if( a === what ) {
      document.body.setAttribute("show-side","")
    } else {
      document.body.setAttribute("show-side",what)
    }
  }
  skipBackToStartOfLink() {
    const elt = this.elt
    const text = elt.value
    const a = elt.selectionStart;
    const b = elt.selectionEnd;
    let left = text.substring(0,b)
    while(left.length > 0 && (left[left.length-1] === "[" || left[left.length-1] === "(")) {
      left = left.substr(0,left.length-1)
    }
    if( left.length > 0 ) {
      let s = left.lastIndexOf("[")
      let r = left.lastIndexOf("(")
      let i = s > r ? s : r
      if( i >= 0 ) {
        elt.selectionStart = elt.selectionEnd = i+1
      }
    }
  }
  skipToEndOfLink() {
    const elt = this.elt
    const text = elt.value
    const a = elt.selectionStart;
    const b = elt.selectionEnd;
    const currentSelection = text.substring(a,b)
    const currentBefore = text.substring(0,a)
    const currentAfter = text.substring(b)
    let startPos = b

    // Naive and fragile, but ok.
    // search forward for first ]. If ](, search forward
    // from that point for next ). If ]], target is end of ]]
    let ca = currentAfter
    let i = ca.indexOf("]")
    if( i === -1 ) {
      return
    }
    if( ca[i+1] === "(" ) {
      let j = ca.substr(i+1).indexOf(")")
      if( j >= 0 ) {
        elt.selectionStart = elt.selectionEnd = b + i + 2 + j
      }
    } else if( ca[i+1] === "]" ) {
      elt.selectionStart = elt.selectionEnd = b + i + 2
    }
  }
  pasteLinkPlain(e) {
    if(!navigator.clipboard) {
      console.log("pasteLink1 Can't access clipboard")
      return true // TODO test
    }
    // example facebook post
    // https://www.facebook.com/groups/bipolarsupportgroup1/posts/24132944553028012/?__cft__[0]=AZUgoDH56iIv7DhOye_ILpc8co0Gds9D0Jkx4-L2sKr6Q3W8ZT_WvG-5WlIDkMSNN8WAbhwlyY-JtHB9GBv8WZs20DU23LfwtQjDkAxWXUI9HrCuvyMFctmPv0XapJGSXnPGN-y-YxIClGf5F1KB6ajQSViSv_suolCG6l_-j3M0nr9RRqWQIGDzN-w25YRaZVM&__tn__=%2CO%2CP-R
    e.preventDefault()
    console.log("pasteLinkPlain")    
    const fb_post_regex = new RegExp("^https://www.facebook.com/[^?]*?/posts/[0-9]+/")
    navigator.clipboard.readText()
      .then(paste => {
        paste = this.relativise(paste)
        this.modifyTextInSelection((currentSelection,currentBefore,currentAfter) => {
          if( currentSelection.length > 0 ) {
            return {
              before: paste,
              selection: "",
              after: ""
            }
          } else {
            return {
              before: ``,
              selection: ``,
              after: paste
            }
          }
        })
      })
      .catch(error => {
        // TODO error
        const msg = "Failed to read clipboard contents"
        console.log({msg,error})
      })
    return true
  }
  pasteVideoLink(e) {
    if(!navigator.clipboard) {
      console.log("pasteLink1 Can't access clipboard")
      return true // TODO test
    }
    e.preventDefault()
    console.log("pasteVideoLink")    
    navigator.clipboard.readText()
      .then(paste => {
        let m
        if( m = paste.match(/^https:\/\/www\.youtube\.com\/watch\?v=(.{11})/) ) {
          let vid = m[1]
          this.modifyTextInSelection((currentSelection,currentBefore,currentAfter) => {
            return {
              before: `${currentSelection}[[youtube:${vid}]]`,
              selection: "",
              after: ""
            }
          })
        } else if( m = paste.match(/^https:\/\/www\.dailymotion\.com\/video\/([a-zA-Z0-9]+)/) ) {
          let vid = m[1]
          this.modifyTextInSelection((currentSelection,currentBefore,currentAfter) => {
            return {
              before: `${currentSelection}[[dailymotion:${vid}]]`,
              selection: "",
              after: ""
            }
          })
        } else {
          return this.pasteLink1(e)
        }
      })
      .catch(error => {
        // TODO error
        const msg = "Failed to read clipboard contents"
        console.log({msg,error})
      })
    return true
  }
  insertAtCursor(t,newline=false) {
    const elt = this.elt
    const text = elt.value
    console.log(1235,{elt,text})
    const a = elt.selectionStart;
    const b = elt.selectionEnd;
    const before = text.substring(0,a)
    const after = text.substring(a)
    const newa = a + t.length
    const newb = b + t.length
    if( newline ) {
      if( a > 0 && before.substr(-1) != "\n" ) {
        t = "\n" + t
      }
    }
    const newtext = before + t + after
    elt.value = newtext
    elt.selectionStart = newa
    elt.selectionEnd = newb
  }
  pasteLink1(e) {
    if(!navigator.clipboard) {
      console.log("pasteLink1 Can't access clipboard")
      return true // TODO test
    }
    e.preventDefault()
    console.log("pasteLink1")    
    const fb_post_regex = new RegExp("^https://www.facebook.com/[^?]*?/posts/[0-9]+/")
    navigator.clipboard.readText()
      .then(paste => {
        paste = this.relativise(paste)
        let m
        if( m = paste.match(fb_post_regex) ) {
          paste = m[0]
        }
        this.modifyTextInSelection((currentSelection,currentBefore,currentAfter) => {
          if( currentSelection.length > 0 ) {
            return {
              before: `[${currentSelection}](${paste})`,
              selection: "",
              after: ""
            }
          } else {
            return {
              before: `[`,
              selection: ``,
              after: `](${paste})`
            }
          }
        })
      })
      .catch(error => {
        // TODO error
        const msg = "Failed to read clipboard contents"
        console.log({msg,error})
      })
    return true
  }
  pasteLink2(e) {
    if(!navigator.clipboard) {
      console.log("pasteLink2 Can't access clipboard")
      return true // TODO test
    }
    e.preventDefault()
    console.log("pasteLink2")    
    navigator.clipboard.readText()
      .then(paste => {
        paste = this.relativise(paste)
        this.modifyTextInSelection((currentSelection,currentBefore,currentAfter) => {
          if( currentSelection.length > 0 ) {
            return {
              before: `${currentSelection} [[${paste}]]`,
              selection: "",
              after: ""
            }
          } else {
            return {
              before: `[[${paste}]]`,
              selection: ``,
              after: ``
            }
          }
        })
      })
      .catch(error => {
        // TODO error
        const msg = "Failed to read clipboard contents"
        console.log({msg,error})
      })
    return true
  }
  linkifySelection1() {
    // []()
    console.log("linkifySel1")    
    this.modifyTextInSelection((currentSelection,currentBefore,currentAfter) => {
      if( currentSelection.length > 0 ) {
        return {
          before: `[`,
          selection: "",
          after: `](${currentSelection})` 
        }
      } else {
        return {
          before: `[`,
          selection: ``,
          after: `]()`
        }
      }
    })
  }
  linkifySelection2() {
    // [[]]
    console.log("linkifySel2")    
    this.modifyTextInSelection((currentSelection,currentBefore,currentAfter) => {
      if( currentSelection.length > 0 ) {
        return {
          before: `[[${currentSelection}]] `,
          selection: "",
          after: ""
        }
      } else {
        return {
          before: `[[`,
          selection: ``,
          after: `]]`
        }
      }
    })
  }
  /**
   *
   * @param: callback(current,before,after)
   *         returns { before, selection, after }
   *         current is replaced with before+selection+after
   *         and selection becomes new selection
   */
  modifyTextInSelection(callback) {
    const elt = this.elt
    const text = elt.value
    console.log(1235,{elt,text})
    const a = elt.selectionStart;
    const b = elt.selectionEnd;
    const currentSelection = text.substring(a,b)
    const currentBefore = text.substring(0,a)
    const currentAfter = text.substring(b)
    const replacement = callback(currentSelection,currentBefore,currentAfter)
    const { before, selection, after } = replacement
    console.log(1236,{before,selection,after},replacement)
    const newText = currentBefore + before + selection + after + currentAfter
    elt.value = newText
    elt.selectionStart = currentBefore.length + before.length
    elt.selectionEnd = currentBefore.length + before.length + selection.length
  }
  insertNewLineBelow() {
    const textarea = this.elt
    const selectionEnd = textarea.selectionEnd
    const text = textarea.value
    const rightOfSelection = text.substr(selectionEnd)
    const nextNewLine = rightOfSelection.indexOf("\n")
    if( nextNewLine === -1 ) {
      // no newlines after selection,
      // so put new line right at the end
      const newText = text + "\n"
      textarea.value = newText
      textarea.selectionStart = newText.length
      textarea.selectionEnd = newText.length 
      //textarea.scrollTop = textarea.scrollHeight
    } else {
      // split textarea.value at location of next newline
      // (newline is at start of right portion)
      // append newline to left portion
      // and set the selection start and end to the end
      // of the left portion
      const insertionPoint = selectionEnd + nextNewLine // p is offset in v of first newline after selection
      const textLeft = text.substr(0,insertionPoint)
      const textRight = text.substr(insertionPoint)
      const newTextLeft = textLeft + "\n"
      const newText = newTextLeft + textRight
      textarea.value = newText 
      textarea.selectionStart = newTextLeft.length
      textarea.selectionEnd = newTextLeft.length
    }
  }
  setFontSize(size) {
    this.elt.setAttribute("font-size",size)
  }
  fixquotes() {
    let a = q("textarea.editor")
    console.log("fixquotes")
    a.value = a.value.replace(/[“”]/g,'"').replace(/[‘’]/g,"'")
  }

  // Mobile nav
  moveLeft() {
    const elt = this.elt
    const text = elt.value
    const a = elt.selectionStart
    const b = elt.selectionEnd
    let i = a == 0 ? 0 : a-1
    elt.selectionStart = elt.selectionEnd = i
    elt.focus()
  }
  moveRight() {
    const elt = this.elt
    const text = elt.value
    const a = elt.selectionStart
    const b = elt.selectionEnd
    let i = b < text.length ? b + 1 : text.length
    elt.selectionStart = elt.selectionEnd = i
    elt.focus()
  }
  movePrevHeader() {
    return this.skipPrev("\n#",1)
  }
  moveNextHeader() {
    return this.skipNext("\n#",1)
  }
  skipPrev(what,offset=0,skipPast=false) {
    const elt = this.elt
    const text = elt.value
    const a = elt.selectionStart
    const b = elt.selectionEnd
    this.elt.focus()
    if( a == 0 ) {
      return
    }
    let i = text.substr(0,a-1).lastIndexOf(what)
    if( i >= 0 ) {
      elt.selectionStart = elt.selectionEnd = i + offset
      //console.log(i,text.substr(i,offset),text.substr(i+offset,100))
    } else {
      if( 
        (text.substr(0,what.length) === what ) ||
        skipPast 
      ) {
        elt.selectionStart = elt.selectionEnd = 0
      }
    }
  }
  skipNext(what,offset=0) {
    const elt = this.elt
    const text = elt.value
    const a = elt.selectionStart
    const b = elt.selectionEnd
    const right = text.substr(b+1)
    let i = right.indexOf(what)
    this.elt.focus()
    if( i >= 0 ) {
      elt.selectionStart = elt.selectionEnd = b+i+1+offset
    } 
  }
  prevLine() {
    const elt = this.elt
    const text = elt.value
    const a = elt.selectionStart
    if( a === 0 ) return
    if( text[a-1] !== "\n" ) {
      this.skipPrev("\n",1,true) 
    }
    return this.skipPrev("\n",1,true)
  }
  nextLine() {
    const elt = this.elt
    const text = elt.value
    const a = elt.selectionStart
    if( a >= text.length) return
    if( text[a] === "\n" ) {
      elt.selectionStart = elt.selectionEnd = a+1
      elt.focus()
    } else {
      this.skipNext("\n",1)
    }
  }
  relativise(targetUrl) {
    // Links to the same subdomain are turned into
    // intra wiki links. Links to a subfolder
    // are turned into relative links, others
    // into links from the root.
    let targetUrl2 = targetUrl.split("?")[0]
    targetUrl2 = targetUrl2.replace(/\/home$/,"")
    function firstDifferingChar(x,y) {
      let l = Math.min(x.length,y.length)
      for(let i=0; i<l; i++) {
        let a = x[i]
        let b = y[i]
        if( a !== b ) return i
      }
      return l
    }
    function commonPrefix(x,y) {
      let i = firstDifferingChar(x,y)
      return x.substr(0,i)
    }
    function dirOf(x) {
      return x.replace(/[^\/]*$/,"",x)
    }
    let currentUrl = window.location.href.split("?")[0]
    let dirOfCurrent = dirOf(currentUrl)
    let dirOfTarget = dirOf(targetUrl2)
    let cp = commonPrefix(dirOfCurrent,dirOfTarget)
    let regex = /^https:\/\/[^/]+\//i;
    if( ! cp.match(regex) ) {
      // link is relative
      console.log("relative",cp)
      return targetUrl
    }
    {
      // determine if we are on the same domain
      let m1 = currentUrl.match(regex)
      let m2 = targetUrl2.match(regex)
      if( m1 && m2 && m1[0] != m2[0] ) {
        console.log("off site")
        return targetUrl
      }
    }
    // if link is in a subdir, use relative
    // else if link is within wiki, strip http and domain
    console.log({cp,dirOfTarget,a:cp.length,b:dirOfTarget.length})
    if( cp.length === dirOfTarget.length ) {
      console.log("equal length")
      return targetUrl2.substr(cp.length)
    }
    if( dirOfCurrent.length == cp.length ) {
      let relTarget = targetUrl2.substr(cp.length)
      return relTarget
    }
    return targetUrl2.replace(regex,"/")
  }
  escapeRegExp(text) {
    return text.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
  }
  setTabStr(tabstr) {
    this.options.tabStr = tabstr
  }
  setIndentLevel(n) {
    n = n | 0
    if( n < 1 ) n = 1
    if( n > 8 ) n = 8
    this.setTabStr(" ".repeat(n))
  }
  incIndentLevelBy(n) {
    n = n | 0
    if( n < -8 ) n = -8
    if( n > 8 ) n = 8
    this.setIndentLevel(this.options.tabStr.length+n)
  }
  insertAtStartOfLines(ta,what) {
  }
  shiftRight(ta,tabstr) {
    if( ! tabstr ) {
      tabstr = this.options.tabStr
    }
    let tw = tabstr.length
    let a = ta.selectionStart
    let aa = a
    let b = ta.selectionEnd
    let bb = b
    let v = ta.value
    while( v[a-1] != "\n" ) {
      a -= 1
      if( a <= 0 ) { a = 0; break }
    }
    while( v[b] != "\n" ) {
      b += 1
      if( b >= v.length ) { b = v.length; break }
    }
    let left = v.substr(0,a)
    let right = v.substr(b)
    let mid = v.substr(a,b-a)
    let nls = mid.replace(/[^\n]/g,"")
    let nnls = nls.length
    let mid1 = mid.replace(/^/mg,tabstr)
    let nv = left + mid1 + right
    let aaa = aa + tw
    let bbb = bb + tw*(nnls+1)
    ta.value = nv
    ta.selectionStart = aaa
    ta.selectionEnd = bbb
  }
  shiftLeft(ta) {
    let tabstr = this.options.tabStr
    let tw = tabstr.length
    let a = ta.selectionStart
    let aa = a
    let b = ta.selectionEnd
    let bb = b
    let v = ta.value
    while( v[a-1] != "\n" ) {
      a -= 1
      if( a <= 0 ) { a = 0; break }
    }
    while( v[b] != "\n" ) {
      b += 1
      if( b >= v.length ) { b = v.length; break }
    }
    let left = v.substr(0,a)
    let right = v.substr(b)
    let mid = v.substr(a,b-a)
    let mids = mid.split("\n")
    let ndel = 0
    let ndel1 = 0
    let mids1 = []
    let re = new RegExp("^"+this.escapeRegExp(this.options.tabStr),"mg")
    let mid0 = mids.shift()
    let mid01 = mid0.replace(re,"")
    let mid0d = mid0.length - mid01.length
    mids1.push(mid01)
    ndel1 = mid0d
    ndel += mid0d
    for(let line of mids) {
      let line1 = line.replace(re,"")  
      ndel += line.length - line1.length
      mids1.push(line1)
    }
    aa -= ndel1
    bb -= ndel
    let mid1 = mids1.join("\n")
    let nv = left + mid1 + right
    ta.value = nv
    ta.selectionStart = aa
    ta.selectionEnd = bb
    console.log("shift left")
  }
}

window.addEventListener("load", _ => {
  const { log } = console
  const q = (x,y=document) => y.querySelector(x)
  const qq = (x,y=document) => Array.from(y.querySelectorAll(x))
  const ajax = new Ajax()
  const ui = new PTUIEdit(ajax)
  window.ptui = ui

  if( window.location.href.match("&version=") ) {
    ui.setDirty();
  }
})

function getLineHeightPx(el) {
  const computed = getComputedStyle(el);
  const lineHeight = computed.lineHeight;

  if (lineHeight === "normal") {
    // "normal" ≈ 1.2 × font-size
    const fontSize = parseFloat(computed.fontSize);
    return fontSize * 1.25;
  }

  if (lineHeight.endsWith("px")) {
    return parseFloat(lineHeight);
  }

  // Handles numeric values like "1.5"
  const fontSize = parseFloat(computed.fontSize);
  return parseFloat(lineHeight) * fontSize;
}

function scrollCurrentLineToMiddle(textarea) {
  const caretIndex = textarea.selectionStart;

  // Get text before caret to find current line number
  const beforeCaret = textarea.value.substring(0, caretIndex);
  const lineNumber = beforeCaret.split("\n").length - 1;

  const lineHeight = getLineHeightPx(textarea);

  // Position of caret line relative to textarea content
  const lineOffset = lineNumber * lineHeight;

  // Scroll so caret line is in middle of textarea's viewport
  const targetScroll = lineOffset - textarea.clientHeight / 2 + lineHeight / 2;

  //textarea.scrollTo({ top: targetScroll, behavior: "smooth" });
  textarea.scrollTo({ top: targetScroll });
}
