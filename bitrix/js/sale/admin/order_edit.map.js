{"version":3,"file":"order_edit.min.js","sources":["order_edit.js"],"names":["BX","namespace","Sale","Admin","OrderEditPage","formId","fieldsUpdaters","fieldsUpdatersContexts","statusesNames","orderId","languageId","siteId","currency","currencyLang","form","adminTabControlId","discountRefreshTimeoutId","getForm","toggleFix","pinObjId","blockObjId","block","pinObj","isFixed","hasClass","addClass","title","message","UnFix","removeClass","Fix","type","window","this","ToggleFix","userOptions","save","disableSavingButtons","disable","i","btn","elements","findChild","document","attr","name","disabled","showDialog","text","alert","onSaveStatusButton","selectId","OrderAjaxer","sendRequest","ajaxRequests","saveStatus","onCancelStatusButton","canceled","toggleCancelDialog","cancelOrder","value","getElementValue","elementId","element","getAllFormData","prepared","ajax","prepareForm","data","unRegisterFieldUpdater","fieldName","fieldUpdater","length","unRegisterProductFieldsUpdaters","basketCode","indexOf","unRegisterFieldsUpdaters","fieldNames","registerFieldsUpdaters","updaters","push","callFieldsUpdaters","orderData","ordered","orderedDone","l","callConcreteFieldUpdater","fieldId","fieldData","context","callback","j","call","currencyFormat","summ","hideCurrency","Currency","restoreFormData","debug","createFormBlocker","scrollHeight","documentElement","clientHeight","height","Math","max","create","props","className","id","style","zIndex","width","backgroundColor","children","top","left","position","background","padding","borderRadius","fontSize","border","html","blockForm","body","appendChild","unBlockForm","blocker","parentNode","removeChild","dialog","toggleClass","setStatus","statusId","changeCancelBlock","params","cancelReasonNode","buttonNode","newBlockContent","CANCELED","DATE_CANCELED","EMP_CANCELED_ID","util","htmlspecialchars","EMP_CANCELED_NAME","textAlign","innerHTML","onclick","onRefreshOrderDataAndSave","submit","onOrderCopy","action","createDiscountsNode","itemCode","itemType","itemDiscounts","discountsList","mode","discountsNode","DISCOUNT_LIST","discountId","DISCOUNT_ID","addDiscountItemRow","itemDiscount","discountParams","table","row","insertRow","itemAttrs","data-discount-id","checkbox","checked","APPLY","attrs","bind","e","setDiscountCheckbox","refreshDiscounts","DESCR","EDIT_PAGE_URL","href","NAME","target","hasAttribute","getAttribute","coll","attribute","onProblemCloseClick","blockId","unmarkOrder","setInterval","refreshOrderData","operation","clearInterval","addProductToBasket","productId","quantity","replaceBasketCode","columns","postData","modifyParams","comment","result","ERROR","select","CAN_USER_EDIT","STATUS_ID","getOrderFields","refreshFormDataAfter","givenFields","demandFields","RESULT_FIELDS","additional","getFlag","display"],"mappings":"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"}