{"version":3,"file":"script.min.js","sources":["script.js"],"names":["window","BX","repo","form","list","comments","makeId","ENTITY_XMIL_ID","ID","inner","keyBoardIsShown","mention","appendToForm","fd","key","val","ii","hasOwnProperty","append","app","exec","addCustomEvent","commentObj","id","text","attachments","this","mentions","prototype","node","getText","replace","RegExp","getInstance","res","type","isArray","join","___id","removeInstance","comment","MPFForm","bindEvents","handlerId","entitiesId","handler","handlerEvents","onMPFUserIsWriting","delegate","writing","onMPFHasBeenDestroyed","reboot","visible","bindHandler","removeCustomEvent","closeWait","onCustomEvent","windowEvents","OnUCUserReply","entityId","authorId","authorName","parseInt","initComment","simpleForm","writingParams","show","OnUCAfterRecordEdit","data","act","showError","showNote","oldObj","newObj","linkEntity","_linkEntity","reinitComment","init","submitClear","submitStart","submit","c","entityHdl","post_data","getForm","ENTITY_XML_ID","REVIEW_TEXT","NOREDIRECT","MODE","AJAX_POST","sessid","bitrix_sessid","post","MobileAjaxWrapper","FormData","ij","length","Wrap","method","url","processData","start","preparePost","callback","proxy","callback_failure","message","xhr","send","addClass","bind","e","unbindAll","removeClass","handleAppData","showWait","hide","link","mobileShowActions","event","isKeyboardShown","enableInVersion","platform","BXMobileAppContext","target","tagName","toUpperCase","isNotEmptyString","getAttribute","eventCancelBubble","PreventDefault","menu","action","push","title","reply","like","RatingLikeComments","getById","vote","voted","List","hidden","BXMobileApp","UI","ActionSheet","buttons","mobileReply","MPL","params","staticParams","formParams","superclass","constructor","apply","arguments","template","thumb","thumbForFile","postCounter","ENTITY_ID","obj","makeThumb","pullNewRecords","add","clearThumb","module_id","command","pullNewRecord","pullNewAuthor","extend","txt","container","isString","util","htmlspecialchars","html","fcParseTemplate","messageFields","FULL_ID","POST_MESSAGE_TEXT","POST_TIMESTAMP","Date","getTime","DATE_TIME_FORMAT","RIGHTS","rights","ob","processHTML","create","attrs","className","style","opacity","height","overflow","HTML","appendChild","curPos","pos","scrollTo","top","scroll","GetWindowScrollPos","size","GetWindowInnerSize","duration","finish","scrollHeight","transition","easing","makeEaseOut","transitions","quart","step","state","scrollTop","innerHeight","complete","cssText","animate","cnt","func","childNodes","ajax","processScripts","SCRIPT","defer","newId","setAttribute","setTimeout","BitrixMobile","LazyLoad","showImages","nav","parentNode","build","createInstance","entity_xml_id"],"mappings":"CAAC,WACA,IAAKA,OAAO,OAASA,OAAO,MAAM,aAAeA,OAAO,OACvD,MAED,IAAIC,GAAKD,OAAO,MACfE,GACCC,QACAC,QACAC,aAEDC,EAAS,SAASC,EAAgBC,GAEjC,MAAOD,GAAiB,KAAOC,EAAK,EAAIA,EAAK,KAG/C,IAAIC,IACHC,gBAAkB,MAClBC,YAEDC,EAAe,SAASC,EAAIC,EAAKC,GAEhC,KAAMA,SAAcA,IAAO,SAC3B,CACC,IAAK,GAAIC,KAAMD,GACf,CACC,GAAIA,EAAIE,eAAeD,GACvB,CACCJ,EAAaC,EAAIC,EAAM,IAAME,EAAK,IAAKD,EAAIC,UAK9C,CACCH,EAAGK,OAAOJ,IAAQC,EAAMA,EAAM,KAGhCf,QAAOmB,IAAIC,KAAK,wBAAyB,KACzCnB,GAAGoB,eAAe,qBAAsB,WAAaZ,EAAMC,gBAAkB,MAC7ET,GAAGoB,eAAe,oBAAqB,WAAaZ,EAAMC,gBAAkB,OAE5E,IAAIY,GAAa,SAASC,EAAIC,EAAMC,GACnCC,KAAKH,GAAKA,CACVG,MAAKF,KAAQA,GAAQ,EACrBE,MAAKD,YAAeA,KACpBC,MAAKC,YAENL,GAAWM,WACVJ,KAAO,GACPC,eACAI,KAAO,KACPC,QAAU,WACT,GAAIN,GAAOE,KAAKF,IAChB,KAAK,GAAIR,KAAMU,MAAKC,SACpB,CACC,GAAID,KAAKC,SAASV,eAAeD,GACjC,CACCQ,EAAOA,EAAKO,QAAQ,GAAIC,QAAOhB,EAAG,MAAOU,KAAKC,SAASX,KAGzD,MAAOQ,IAMTF,GAAWW,YAAc,SAASV,EAAIC,EAAMC,GAC3C,GAAIS,GAAM,IACV,KAAKjC,EAAGkC,KAAKC,QAAQb,IAAOA,GAAMA,EAAG,UAAYrB,EAAK,YAAYqB,EAAG,UACrE,CACCW,EAAMX,MAEF,IAAIrB,EAAK,YAAYqB,EAAGc,KAAK,MAClC,CACCH,EAAMhC,EAAK,YAAYqB,EAAGc,KAAK,UAGhC,CACCH,EAAM,GAAIZ,GAAWC,EAAIC,EAAMC,EAC/BS,GAAII,MAAQf,EAAGc,KAAK,IACpBnC,GAAK,YAAYqB,EAAGc,KAAK,MAAQH,EAElC,MAAOA,GAERZ,GAAWiB,eAAiB,SAASC,GACpC,GAAIA,GAAWA,EAAQ,eACftC,GAAK,YAAYsC,EAAQ,UAElC,IAAIC,GAAU,SAASlB,GACtBG,KAAKgB,YACLxC,GAAK,QAAQwB,KAAKiB,WAAajB,IAC/BA,MAAKkB,aAELlB,MAAKc,QAAU,IAEfd,MAAKiB,UAAYpB,CACjBG,MAAKmB,QAAU,IACfnB,MAAKoB,eACJC,mBAAqB9C,EAAG+C,SAAStB,KAAKuB,QAASvB,MAC/CwB,sBAAwBjD,EAAG+C,SAAStB,KAAKyB,OAAQzB,MAGlDA,MAAK0B,QAAU,KAEf1B,MAAK2B,YAAcpD,EAAG+C,SAAStB,KAAK2B,YAAa3B,KACjDzB,GAAGoB,eAAerB,OAAQ,qBAAsB0B,KAAK2B,YACrD,IAAIpD,EAAG,OACNyB,KAAK2B,YAAYpD,EAAG,OAAOgC,YAAYP,KAAKiB,YAG9CF,GAAQb,WACPyB,YAAc,SAASR,GACtB,GAAIA,GAAWA,EAAQtB,IAAMG,KAAKiB,UAClC,CACCjB,KAAKmB,QAAUA,CAEf5C,GAAGqD,kBAAkBtD,OAAQ,qBAAsB0B,KAAK2B,YAExD,KAAK,GAAIrC,KAAMU,MAAKoB,cACpB,CACC,GAAIpB,KAAKoB,cAAc7B,eAAeD,GACtC,CACCf,EAAGoB,eAAeK,KAAKmB,QAAS7B,EAAIU,KAAKoB,cAAc9B,KAIzDU,KAAK6B,WACLtD,GAAGuD,cAAc9B,KAAM,gBAAiBA,SAG1CgB,WAAa,WACZhB,KAAK+B,cACJC,cAAgBzD,EAAG+C,SAAS,SAASW,EAAUC,EAAUC,GACxD,GAAInC,KAAKkB,WAAWe,GACpB,CACC,GAAInB,IAAWmB,EAAU,EACzBC,GAAWE,SAASF,EACpB,IAAIA,EAAW,GAAKC,EACpB,CACCrB,EAAUd,KAAKqC,YAAYvB,EAAS,GAAI,MACxCA,GAAQb,SAASkC,GAAc,SAAWD,EAAW,IAAMC,EAAa,SACxE,IAAIrC,GAAQE,KAAKmB,SAAWnB,KAAKmB,QAAQmB,WAAatC,KAAKmB,QAAQmB,WAAWC,cAAc,SAAWzB,EAAQhB,IAC/GgB,GAAQhB,KAAOA,GAAQA,GAAQ,GAAK,GAAK,KAAOqC,EAAa,KAE9DnC,KAAKwC,KAAK1B,EAASA,EAAQhB,KAAM,SAEhCE,MAEHyC,oBAAsBlE,EAAG+C,SAAS,SAASW,EAAUpC,EAAI6C,EAAMC,GAE9D,GAAI3C,KAAKkB,WAAWe,GAAW,CAC9B,GAAIU,IAAQ,OACZ,CACC3C,KAAKwC,MAAMP,EAAUpC,GAAK6C,EAAK,iBAAkBA,EAAK,sBAElD,IAAIA,EAAK,gBACd,CACC1C,KAAK4C,WAAWX,EAAUpC,GAAK6C,EAAK,qBAEhC,IAAIA,EAAK,aACd,CACC1C,KAAK6C,UAAUZ,EAAUpC,GAAK6C,EAAK,iBAGnC1C,MAGJzB,GAAGoB,eAAerB,OAAQ,gBAAiB0B,KAAK+B,aAAaC,cAC7DzD,GAAGoB,eAAerB,OAAQ,sBAAuB0B,KAAK+B,aAAaU,sBAEpEhB,OAAS,SAAS5B,EAAIiD,EAAQC,GAC7B,IAAK,GAAIzD,KAAMU,MAAKoB,cACpB,CACC,GAAIpB,KAAKoB,cAAc7B,eAAeD,GACtC,CACCf,EAAGqD,kBAAkB5B,KAAKmB,QAAS7B,EAAIU,KAAKoB,cAAc9B,KAG5DU,KAAK2B,YAAYoB,IAElBC,WAAa,SAASnD,EAAI6C,GACzB,GAAI1C,KAAKmB,UAAY,KACrB,CACCnB,KAAKiD,YAAc1E,EAAG+C,SAAS,WAAWtB,KAAKgD,WAAWnD,EAAI6C,IAAS1C,KACvEzB,GAAGoB,eAAeK,KAAM,eAAgBA,KAAKiD,iBAG9C,CACC,GAAIjD,KAAK,eACRzB,EAAGqD,kBAAkB5B,KAAM,eAAgBA,KAAK,eAEjDA,MAAKkB,WAAWrB,GAAM6C,CACtB1C,MAAKc,QAAUd,KAAKkD,eAAerD,IAAMA,EAAI,IAC7CG,MAAKmB,QAAQgC,KAAKnD,KAAKc,WAGzBS,QAAU,SAAST,GAClBvC,EAAGuD,cAAcxD,OAAQ,qBAAsBwC,EAAQ,MAAM,GAAIA,EAAQ,MAAM,MAEhFoC,cAAgB,SAASpC,GACxB,GAAIjB,IAAMiB,EAAQ,MAAM,GAAI,EAC5BlB,GAAWiB,eAAeC,EAC1B,OAAOd,MAAKqC,YAAYxC,EAAI,QAE7BwC,YAAc,SAASxC,EAAIC,EAAM4C,GAChC,GAAI5B,GAAUlB,EAAWW,YAAYV,EAAIC,EAAM4C,EAC/C,IAAI5B,EAAQ,WAAa,IACzB,CACCvC,EAAGoB,eAAemB,EAAS,WAAYvC,EAAG+C,SAAS/C,EAAG+C,SAAStB,KAAKoD,YAAapD,OACjFzB,GAAGoB,eAAemB,EAAS,UAAWvC,EAAG+C,SAAS/C,EAAG+C,SAAStB,KAAKqD,YAAarD,OAChFzB,GAAGoB,eAAemB,EAAS,WAAYvC,EAAG+C,SAAS/C,EAAG+C,SAAStB,KAAKsD,OAAQtD,OAC5EzB,GAAGoB,eAAemB,EAAS,UAAWvC,EAAG+C,SAAS/C,EAAG+C,SAAS,SAASiC,EAAGzD,GACzEE,KAAK4C,UAAU9B,EAAShB,EACxBE,MAAKoD,YAAYtC,IACfd,OACHc,GAAQ,SAAW,IAEpB,MAAOA,IAER0B,KAAO,SAAS3C,EAAIC,EAAM4C,GACzB1C,KAAKc,QAAUd,KAAKqC,YAAYxC,EAAIC,EAAM4C,EAC1CnE,GAAGuD,cAAc9B,KAAKmB,QAAS,sBAAuBnB,KAAMF,EAAM4C,GAClE1C,MAAKmB,QAAQqB,KAAKxC,KAAKc,UAAY4B,EACnCnE,GAAGuD,cAAc9B,KAAKmB,QAAS,qBAAsBnB,KAAMF,EAAM4C,GACjE,OAAO,OAERU,YAAc,SAAStC,GACtBlB,EAAWiB,eAAeC,EAC1B,IAAId,KAAKc,SAAWA,EACpB,CAECd,KAAKc,QAAUd,KAAKqC,aAAavB,EAAQjB,GAAG,GAAI,GAAI,MACpDG,MAAKmB,QAAQgC,KAAKnD,KAAKc,WAGzBuC,YAAc,SAASvC,EAAShB,EAAMC,GACrCxB,EAAGuD,cAAcxD,OAAQ,wBAAyBwC,EAAQjB,GAAG,GAAIiB,EAAQjB,GAAG,GAAIiB,EAASd,KAAMF,EAAMC,KAEtGuD,OAAS,SAASxC,GACjB,GAAIhB,GAAOgB,EAAQV,UAClBL,EAAce,EAAQf,YACtByD,EAAYxD,KAAKkB,WAAWJ,EAAQjB,GAAG,IACvC4D,EAAYzD,KAAKmB,QAAQuC,SACxBC,cAAgB7C,EAAQjB,GAAG,GAC3B+D,YAAc9D,EACd+D,WAAa,IACbC,KAAO,SACPC,UAAY,IACZlE,GAAKiB,EAAQjB,GACbmE,OAASzF,EAAG0F,kBAEbC,EAAO,GAAI5F,QAAO6F,kBAClBhF,EAAK,GAAIb,QAAO8F,SAChB9E,CAGD,IAAIwB,EAAQjB,GAAG,GAAK,EACpB,CACC4D,EAAU,iBAAmB,MAC7BA,GAAU,WAAa3E,GAAKgC,EAAQjB,GAAG,GACvC,IAAI4D,EAAU,OACd,CACCA,EAAU,OAAS,MACnBA,GAAU,WAAa3C,EAAQjB,GAAG,IAGpC,GAAI2D,EAAU,UACd,CACC,IAAKlE,IAAMkE,GAAU,UACrB,CACC,GAAIA,EAAU,UAAUjE,eAAeD,GACvC,CACCmE,EAAUnE,GAAMkE,EAAU,UAAUlE,KAKvCf,EAAGuD,cAAcxD,OAAQ,kBAAmBwC,EAAQjB,GAAG,GAAIiB,EAAQjB,GAAG,GAAIG,KAAMyD,GAChF,KAAKnE,IAAMmE,GACX,CACC,GAAIA,EAAUlE,eAAeD,GAC7B,CACCJ,EAAaC,EAAIG,EAAImE,EAAUnE,KAGjC,GAAIS,EACJ,CACC,IAAK,GAAIsE,GAAK,EAAGA,EAAKtE,EAAYuE,OAAQD,IAC1C,CACCnF,EAAaC,EAAIY,EAAYsE,GAAI,aAActE,EAAYsE,GAAI,gBAIjEH,EAAKK,MACJC,OAAQ,OACRC,IAAKjB,EAAU,OACfd,QACAjC,KAAM,OACNiE,YAAc,KACdC,MAAQ,MACRC,YAAc,MACdC,SAAUtG,EAAGuG,MAAM,SAASpC,GAC3BnE,EAAGuD,cAAcxD,OAAQ,oBAAqBwC,EAAQjB,GAAG,GAAIiB,EAAQjB,GAAG,GAAIG,KAAM0C,EAAM5B,GACxF,IAAI4B,EAAK,gBACR1C,KAAK4C,UAAU9B,EAAS4B,EAAK,qBAE7BnE,GAAGuD,cAAcxD,OAAQ,sBAAuBwC,EAAQjB,GAAG,GAAIiB,EAAQjB,GAAG,GAAIG,KAAM0C,EAAM5B,KACzFd,MACH+E,iBAAkBxG,EAAG+C,SAAS,SAASoB,GACtCnE,EAAGuD,cAAcxD,OAAQ,oBAAqBwC,EAAQjB,GAAG,GAAIiB,EAAQjB,GAAG,GAAIG,KAAM0C,EAAM5B,GACxFd,MAAK4C,UAAU9B,EAASvC,EAAGyG,QAAQ,+BACjChF,OAEJkE,GAAKe,IAAIC,KAAK/F,EAEda,MAAKoD,YAAYtC,IAElB8B,UAAY,SAAS9B,EAAShB,GAC7B,GAAIvB,EAAGkC,KAAKC,QAAQI,GACnBA,EAAUd,KAAKqC,YAAYvB,EAAS,MACrC,IAAIX,EACJL,GAAO,2EACL,MAAQvB,EAAGyG,QAAQ,YAAc,aAAelF,EAAO,QACzD,IAAIgB,GAAWA,EAAQX,KACvB,CACC5B,EAAG4G,SAASrE,EAAQX,KAAM,mCAC1B,UACQW,GAAQf,aAAe,aAC3Be,EAAQf,YAAYuE,QAAU,EAElC,CACC/F,EAAG6G,KAAKtE,EAAQX,KAAM,QAAS5B,EAAGuG,MAAM,SAASO,GAChD9G,EAAG+G,UAAUxE,EAAQX,KACrB5B,GAAGgH,YAAYzE,EAAQX,KAAM,mCAC7BH,MAAKmB,QAAQL,QAAUA,CACvBd,MAAKmB,QAAQmB,WAAWkD,cAAc1E,EAAQhB,KAAM,OAClDE,YAQA,IAAIF,EACT,IASD+C,SAAW,SAAShD,EAAIC,KAexB2F,SAAW,WACVzF,KAAKmB,QAAQuE,MACb1F,MAAKmB,QAAQsE,YAEd5D,UAAY,WACX7B,KAAKmB,QAAQU,aAIfd,GAAQ4E,KAAO,SAAShC,EAAelF,GACtC,GAAIoB,GAAKpB,EAAK,KACdD,GAAK,QAAQqB,GAAOrB,EAAK,QAAQqB,IAAO,GAAKkB,GAAQlB,EACrDrB,GAAK,QAAQqB,GAAImD,WAAWW,EAAelF,GAG5CH,QAAOsH,kBAAoB,SAASjC,EAAe7E,EAAIuG,GACtDA,EAAIA,GAAK/G,OAAOuH,KAEhB,IAAIC,GAAmBxH,OAAOmB,IAAIsG,gBAAgB,KAAOzH,OAAO0H,UAAY,MACpEC,mBAAmBH,kBACnB/G,EAAMC,eAGd,IAAG8G,EACH,CACC,MAAO,MAGR,GACCT,GACGA,EAAEa,QACFb,EAAEa,OAAOC,UAEXd,EAAEa,OAAOC,QAAQC,eAAiB,KAEjCf,EAAEa,OAAOC,QAAQC,eAAiB,OAC9B7H,EAAGkC,KAAK4F,iBAAiBhB,EAAEa,OAAOI,aAAa,mBAItD,CACC,MAAO,MAGR/H,EAAGgI,kBAAkBlB,EACrB9G,GAAGiI,eAAenB,EAElB,IAAIlF,GAAO5B,EAAG,UAAYK,EAAO+E,EAAe7E,IAC/C2H,KAAWC,CAEZ,IAAIvG,EAAKmG,aAAa,sBAAwB,IAC7CG,EAAKE,MACJC,MAAOrI,EAAGyG,QAAQ,gBAClBH,SAAU,WACTrG,EAAK,QAAQmF,GAAekD,MAAMtI,EAAG,UAAYK,EAAO+E,EAAe7E,GAAM,oBAGhF,IAAIgI,EACJ,IAAK3G,EAAKmG,aAAa,mBAAqB,aAAgBhI,OAAO,wBACjEwI,EAAOxI,OAAOyI,mBAAmBC,QAAQ7G,EAAKmG,aAAa,qBAAuBQ,EACpF,CACCA,EAAK,uBAA0BA,EAAK,wBAA0BvI,EAAG+C,SAASwF,EAAKG,KAAMH,EACrFL,GAAKE,MAAMC,MAAQE,EAAKI,MAAQ3I,EAAGyG,QAAQ,iBAAmBzG,EAAGyG,QAAQ,iBACxEH,SAAUiC,EAAK,wBAChBL,GAAKE,MAAOC,MAAOrI,EAAGyG,QAAQ,gBAC7BH,SAAU,WAAavG,OAAOyI,mBAAmBI,KAAKhH,EAAKmG,aAAa,sBAG1E,GAAInG,EAAKmG,aAAa,qBAAuB,IAC5CG,EAAKE,MACJC,MAAOrI,EAAGyG,QAAQ,gBAClBH,SAAU,WAAarG,EAAK,QAAQmF,GAAehB,IAAIxC,EAAKmG,aAAa,mBAAoBxH,EAAI,UACnG,IAAIqB,EAAKmG,aAAa,yBAA2B,IACjD,CACC,GAAIc,GAASjH,EAAKmG,aAAa,6BAA+B,QAC9DG,GAAKE,MACJC,MAAQQ,EAAS7I,EAAGyG,QAAQ,gBAAkBzG,EAAGyG,QAAQ,gBACzDH,SAAU,WACTrG,EAAK,QAAQmF,GAAehB,IAAIxC,EAAKmG,aAAa,uBACjDjG,QAAQ,WAAa+G,EAAS,OAAS,QACvC/G,QAAQ,WAAa+G,EAAS,OAAS,QACvCtI,EAAI,eAIR,GAAIqB,EAAKmG,aAAa,uBAAyB,IAC9CG,EAAKE,MACJC,MAAOrI,EAAGyG,QAAQ,kBAClBH,SAAU,WAAarG,EAAK,QAAQmF,GAAehB,IAAIxC,EAAKmG,aAAa,qBAAsBxH,EAAI,YACrG,IAAI2H,EAAKnC,OAAS,EAClB,CACCoC,EAAS,GAAIpI,QAAO+I,YAAYC,GAAGC,aAAcC,QAASf,GAAQ,eAClEC,GAAOlE,OAER,MAAO,OAERlE,QAAOmJ,YAAc,SAAS9D,EAAe0B,GAC5C9G,EAAGgI,kBAAkBlB,EACrB9G,GAAGiI,eAAenB,EAClB7G,GAAK,QAAQmF,GAAekD,MAAMxB,EAAEa,OACpC,OAAO,OAGR,IAAI/C,GAAO,SAAS7E,GAEnBC,EAAGmJ,IAAM,SAASC,EAAQC,EAAcC,GAEvCtJ,EAAGmJ,IAAII,WAAWC,YAAYC,MAAMhI,KAAMiI,UAE1CjI,MAAKkI,SAAW3J,EAAGyG,QAAQ,sBAC3BhF,MAAKmI,MAAQ5J,EAAGyG,QAAQ,mBACxBhF,MAAKoI,aAAe7J,EAAGyG,QAAQ,wBAE/BzG,GAAGqD,kBAAkBtD,EAAQ,qBAAsB0B,KAAK+B,aAAa,sBACrExD,GAAGqD,kBAAkBtD,EAAQ,mBAAoB0B,KAAK+B,aAAa,oBAEnE/B,MAAKqI,YAAc,CACnBrI,MAAK+B,aAAa,wBAA0BxD,EAAG+C,SAAS,SAASqC,EAAe2E,EAAWxH,EAASyH,EAAKzI,EAAMC,GAC9G,GAAIC,KAAK2D,eAAiBA,EAAe,CACxC,GAAI9D,IAAM8D,EAAgB2E,EAAY,EAAIA,EAAY,OAAStI,KAAKqI,cACpErI,MAAKwI,UAAU3I,EAAIiB,EAAShB,EAAMC,EAClCC,MAAKyI,eAAe9E,EAAgB,IAAM2E,GAAa,SAEtDtI,KACHA,MAAK+B,aAAa,sBAAwBxD,EAAG+C,SAAS,SAASqC,EAAe2E,EAAWC,EAAK7F,EAAM5B,GACnG,GAAId,KAAK2D,eAAiBA,EAAe,CACxC3D,KAAK0I,IAAI5H,EAAS4B,EAAK,aAAcA,EAAM,KAAM,YAEhD1C,KACHA,MAAK+B,aAAa,oBAAsBxD,EAAG+C,SAAS,SAASqC,EAAe2E,EAAWC,EAAK7F,EAAM5B,GACjG,GAAId,KAAK2D,eAAiBA,EAC1B,CACC3D,KAAKyI,eAAe9E,EAAgB,MAAQ,OAC5C3D,MAAKyI,eAAe9E,EAAgB,IAAM2E,GAAa,MACvDtI,MAAK2I,WAAW7H,KAEfd,KACHA,MAAK+B,aAAa,UAAYxD,EAAG+C,SAAS,SAASoB,GAClD,GAAIiF,GAASjF,EAAKiF,MAClB,IAAIjF,EAAKkG,WAAa,eAAiBlG,EAAKmG,SAAW,kBACtDlB,EAAO,kBAAoB3H,KAAK2D,eAAmBgE,EAAO,WAAa,IAAQpJ,EAAGyG,QAAQ,WAAa,GACxG,CACC,GAAItC,EAAKmG,SAAW,kBAAoBlB,EAAO,MAC9C3H,KAAK8I,cAAcnB,OACf,IAAIjF,EAAKmG,SAAW,SACxB7I,KAAK+I,cAAcpB,EAAO,WAAYA,EAAO,QAASA,EAAO,aAE7D3H,KAEHzB,GAAGoB,eAAerB,EAAQ,mBAAoB0B,KAAK+B,aAAa,oBAChExD,GAAGoB,eAAerB,EAAQ,qBAAsB0B,KAAK+B,aAAa,sBAClExD,GAAGoB,eAAerB,EAAQ,uBAAwB0B,KAAK+B,aAAa,wBACpExD,GAAGoB,eAAerB,EAAQ,SAAU0B,KAAK+B,aAAa,UAEtD,IAAI6F,EAAa,mBAAqB,IACrC7G,EAAQ4E,KAAK3F,KAAK2D,cAAekE,EAElCrJ,GAAK,QAAQwB,KAAK2D,eAAiB3D,IACnC,OAAOA,MAERzB,GAAGyK,OAAOzK,EAAGmJ,IAAKpJ,EAAO,UACzBC,GAAGmJ,IAAIxH,UAAUiD,KAAO,YACxB5E,GAAGmJ,IAAIxH,UAAUsI,UAAY,SAAS3I,EAAImF,EAASiE,EAAKlJ,GACvD,GAAImJ,GAAalE,EAAQ7E,MAAQ5B,EAAG,UAAYsB,EAAGc,KAAK,KAAO,SAC/D,KAAKuI,EACL,CACC,GAAIpJ,GAAQvB,EAAGkC,KAAK0I,SAASF,GAAOA,EAAM,EAC1CnJ,GAAOvB,EAAG6K,KAAKC,iBAAiBvJ,GAAMO,QAAQ,OAAQ,SACtDP,GAAOA,EAAKO,QAAQ,OAAQ,IAC3BA,QAAQ,iBAAkB,KAC1BA,QAAQ,qCAAsC,MAC9CA,QAAQ,OAAQ,UAEjB,IAAIiJ,GAAOhL,EAAOiL,iBACfC,eAAkBC,QAAU5J,EAAI6J,kBAAoB5J,EAAM6J,gBAAkB,GAAIC,OAAOC,UAAY,OACnGC,iBAAmB9J,KAAK2H,OAAOmC,iBAAkBC,OAAS/J,KAAKgK,QAChElK,GAAQ,GAAKE,KAAKoI,aAAepI,KAAKmI,OAAS8B,CAEjDA,GAAK1L,EAAG2L,YAAYZ,EAAM,MAC1BJ,GAAY3K,EAAG4L,OAAO,OACrBC,OAASvK,GAAM,UAAYA,EAAGc,KAAK,KAAO,SAAW0J,UAAc,wBACnEC,OAASC,QAAU,EAAGC,OAAS,EAAGC,SAAU,UAC5CnB,KAAOW,EAAGS,MACXnM,GAAG,UAAYsB,EAAG,GAAK,QAAQ8K,YAAYzB,EAE3C,IAAI/I,GAAO+I,EACV0B,EAASrM,EAAGsM,IAAI1K,EACjB7B,GAAOwM,SAAS,EAAGF,EAAOG,IAE1B,IAAIC,GAASzM,EAAG0M,qBACfC,EAAO3M,EAAG4M,oBAEX,IAAK5M,GAAG,WACP6M,SAAW,IACXzG,OAAU4F,QAAU,EAAGC,OAAS,GAChCa,QAAWd,QAAS,IAAKC,OAASrK,EAAKmL,cACvCC,WAAahN,EAAGiN,OAAOC,YAAYlN,EAAGiN,OAAOE,YAAYC,OACzDC,KAAO,SAASC,GACf1L,EAAKmK,MAAME,OAASqB,EAAMrB,OAAS,IACnCrK,GAAKmK,MAAMC,QAAUsB,EAAMtB,QAAU,GAErC,IAAIS,EAAOc,UAAY,GAAKlB,EAAOG,IAAOC,EAAOc,UAAYZ,EAAKa,YAClE,CACCzN,EAAOwM,SAAS,EAAGE,EAAOc,UAAYD,EAAMrB,UAI9CwB,SAAW,WACV7L,EAAKmK,MAAM2B,QAAU,MAEnBC,SAEJ,IAAIC,GAAM,EACVC,EAAO,WAEND,GACA,IAAIA,EAAM,IACV,CACC,GAAIhM,GAAO5B,EAAG,UAAYsB,EAAGc,KAAK,KAAO,SACzC,IAAIR,GAAQA,EAAKkM,WAAW/H,OAAS,EACpC/F,EAAG+N,KAAKC,eAAetC,EAAGuC,YAE1BjO,GAAGkO,MAAML,EAAMpM,SAGlBzB,GAAGkO,MAAML,EAAMpM,QAEhBzB,EAAG4G,SAAS+D,EAAW,4BACvBlE,GAAQ7E,KAAO+I,CACf,OAAOA,GAER3K,GAAGmJ,IAAIxH,UAAUyI,WAAa,SAAS3D,GACtC,GAAIA,GAAWzG,EAAGyG,EAAQ7E,MAC1B,CACC5B,EAAGgH,YAAYP,EAAQ7E,KAAM,8BAG/B5B,GAAGmJ,IAAIxH,UAAUwI,IAAM,SAAS5H,EAAS4L,EAAOhK,GAC/C,GAAInE,EAAGkC,KAAKC,QAAQI,GACpB,CACCvC,EAAGmJ,IAAII,WAAWY,IAAIV,MAAMhI,KAAMiI,eAE9B,IAAI1J,EAAGuC,EAAQ,SACpB,CACCA,EAAQ,QAAQ6L,aAAa,KAAM,UAAYD,EAAM/L,KAAK,KAAO,SACjEpC,GAAGmJ,IAAII,WAAWY,IAAIV,MAAMhI,MAAO0M,EAAOhK,EAAM,KAAM,eAGvD,CACCnE,EAAGmJ,IAAII,WAAWY,IAAIV,MAAMhI,MAAO0M,EAAOhK,IAE3C,GAAIpE,EAAO,iBAAmBA,EAAO,gBAAgB,YACpDsO,WAAW,WAAatO,EAAOuO,aAAaC,SAASC,cAAiB,KAExExO,GAAGmJ,IAAIxH,UAAUgF,KAAO,WACvB,GAAI3G,EAAGyB,KAAKgN,KACXzO,EAAG4G,SAASnF,KAAKgN,IAAIC,WAAY,8BAClC1O,GAAGmJ,IAAII,WAAW5C,KAAK8C,MAAMhI,KAAMiI,WAEpC1J,GAAGmJ,IAAIxH,UAAUgN,MAAQ,WACxB,GAAI3O,EAAGyB,KAAKgN,KACXzO,EAAGgH,YAAYvF,KAAKgN,IAAIC,WAAY,8BACrC1O,GAAGmJ,IAAII,WAAWoF,MAAMlF,MAAMhI,KAAMiI,WAErC1J,GAAGmJ,IAAIxH,UAAU8L,SAAW,WAC3B,GAAIzN,EAAGyB,KAAKgN,KACXzO,EAAGgH,YAAYvF,KAAKgN,IAAIC,WAAY,8BACrC1O,GAAGmJ,IAAII,WAAWkE,SAAShE,MAAMhI,KAAMiI,WAExC1J,GAAGmJ,IAAIxH,UAAUuF,SAAW,SAAS5F,GACpC,GAAIqJ,GAAY3K,EAAG,UAAYyB,KAAK2D,cAAgB,IAAM9D,EAAK,SAC/D,IAAIA,EAAK,GAAKqJ,EACb3K,EAAG4G,SAAS+D,EAAW,6BAEzB3K,GAAGmJ,IAAIxH,UAAU2B,UAAY,SAAShC,GACrC,GAAIqJ,GAAY3K,EAAG,UAAYyB,KAAK2D,cAAgB,IAAM9D,EAAK,SAC/D,IAAIA,EAAK,GAAKqJ,EACb3K,EAAGgH,YAAY2D,EAAW,6BAE5B3K,GAAGmJ,IAAIyF,eAAiB,SAASxF,EAAQC,EAAcC,GACtD,MAAO,IAAKtJ,GAAGmJ,IAAIC,EAAQC,EAAcC,GAG1CtJ,GAAGmJ,IAAInH,YAAc,SAAS6M,GAC7B,MAAO5O,GAAK,QAAQ4O,GAGrB7O,GAAGoB,eAAerB,EAAQ,uBAAwB,SAASqF,SACnDnF,GAAK,QAAQmF,IAErBpF,GAAGuD,cAAc,yBAA0B,aAC3CvD,GAAGqD,kBAAkB,yBAA0B,WAAYuB,EAAK7E,KAEjEC,GAAGoB,eAAe,yBAA0B,WAAYwD,EAAK7E,SAC7D,IAAIA,OAAO,UACV6E,EAAK7E"}