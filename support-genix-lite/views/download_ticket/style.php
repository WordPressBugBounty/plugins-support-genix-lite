<style>
/* base style */
#pdf-download-content p,
#pdf-download-content ol,
#pdf-download-content ul,
#pdf-download-content pre {
    font-size: 14px;
    font-weight: 400;
    line-height: 24px;
    color: #333;
    margin: 0;
}
#pdf-download-content pre {
    background-color: #f9f9f9;
    border-radius: 4px;
    padding: 7px;
}
/* grid style */
#pdf-download-content .sg_dt__row {
    display: -ms-flexbox;
    display: flex;
    -ms-flex-wrap: wrap;
    flex-wrap: wrap;
    margin-right: -15px;
    margin-left: -15px;
    -webkit-box-sizing: border-box;
    -moz-box-sizing: border-box;
    box-sizing: border-box;
}
#pdf-download-content .sg_dt__row-inline {
    display: -ms-inline-flexbox;
    display: inline-flex;
    -ms-flex-wrap: wrap;
    flex-wrap: wrap;
    margin-right: -15px;
    margin-left: -15px;
    -webkit-box-sizing: border-box;
    -moz-box-sizing: border-box;
    box-sizing: border-box;
}
#pdf-download-content .sg_dt__align-items-start {
    -ms-flex-align: start;
    align-items: flex-start;
}
#pdf-download-content .sg_dt__align-items-center {
    -ms-flex-align: center;
    align-items: center;
}
#pdf-download-content .sg_dt__align-items-end {
    -ms-flex-align: end;
    align-items: flex-end;
}
#pdf-download-content .sg_dt__align-self-start {
    -ms-flex-item-align: start;
    align-self: flex-start;
}
#pdf-download-content .sg_dt__align-self-center {
    -ms-flex-item-align: center;
    align-self: center;
}
#pdf-download-content .sg_dt__align-self-end {
    -ms-flex-item-align: end;
    align-self: flex-end;
}
#pdf-download-content .sg_dt__justify-content-start {
    -ms-flex-pack: start;
    justify-content: flex-start;
}
#pdf-download-content .sg_dt__justify-content-center {
    -ms-flex-pack: center;
    justify-content: center;
}
#pdf-download-content .sg_dt__justify-content-end {
    -ms-flex-pack: end;
    justify-content: flex-end;
}
#pdf-download-content .sg_dt__justify-content-around {
    -ms-flex-pack: distribute;
    justify-content: space-around;
}
#pdf-download-content .sg_dt__justify-content-between {
    -ms-flex-pack: justify;
    justify-content: space-between;
}
#pdf-download-content .sg_dt__col,
#pdf-download-content .sg_dt__col-1,
#pdf-download-content .sg_dt__col-2,
#pdf-download-content .sg_dt__col-3,
#pdf-download-content .sg_dt__col-4,
#pdf-download-content .sg_dt__col-5,
#pdf-download-content .sg_dt__col-6,
#pdf-download-content .sg_dt__col-7,
#pdf-download-content .sg_dt__col-8,
#pdf-download-content .sg_dt__col-9,
#pdf-download-content .sg_dt__col-10,
#pdf-download-content .sg_dt__col-11,
#pdf-download-content .sg_dt__col-12,
#pdf-download-content .sg_dt__col-auto {
    position: relative;
    width: 100%;
    padding-right: 15px;
    padding-left: 15px;
    -webkit-box-sizing: border-box;
    -moz-box-sizing: border-box;
    box-sizing: border-box;
}
#pdf-download-content .sg_dt__col {
    -ms-flex-preferred-size: 0;
    flex-basis: 0;
    -ms-flex-positive: 1;
    flex-grow: 1;
    max-width: 100%;
}
#pdf-download-content .sg_dt__col-1 {
    -ms-flex: 0 0 8.333333%;
    flex: 0 0 8.333333%;
    max-width: 8.333333%;
}
#pdf-download-content .sg_dt__col-2 {
    -ms-flex: 0 0 16.666667%;
    flex: 0 0 16.666667%;
    max-width: 16.666667%;
}
#pdf-download-content .sg_dt__col-3 {
    -ms-flex: 0 0 25%;
    flex: 0 0 25%;
    max-width: 25%;
}
#pdf-download-content .sg_dt__col-4 {
    -ms-flex: 0 0 33.333333%;
    flex: 0 0 33.333333%;
    max-width: 33.333333%;
}
#pdf-download-content .sg_dt__col-5 {
    -ms-flex: 0 0 41.666667%;
    flex: 0 0 41.666667%;
    max-width: 41.666667%;
}
#pdf-download-content .sg_dt__col-6 {
    -ms-flex: 0 0 50%;
    flex: 0 0 50%;
    max-width: 50%;
}
#pdf-download-content .sg_dt__col-7 {
    -ms-flex: 0 0 58.333333%;
    flex: 0 0 58.333333%;
    max-width: 58.333333%;
}
#pdf-download-content .sg_dt__col-8 {
    -ms-flex: 0 0 66.666667%;
    flex: 0 0 66.666667%;
    max-width: 66.666667%;
}
#pdf-download-content .sg_dt__col-9 {
    -ms-flex: 0 0 75%;
    flex: 0 0 75%;
    max-width: 75%;
}
#pdf-download-content .sg_dt__col-10 {
    -ms-flex: 0 0 83.333333%;
    flex: 0 0 83.333333%;
    max-width: 83.333333%;
}
#pdf-download-content .sg_dt__col-11 {
    -ms-flex: 0 0 91.666667%;
    flex: 0 0 91.666667%;
    max-width: 91.666667%;
}
#pdf-download-content .sg_dt__col-12 {
    -ms-flex: 0 0 100%;
    flex: 0 0 100%;
    max-width: 100%;
}
#pdf-download-content .sg_dt__col-auto {
    -ms-flex: 0 0 auto;
    flex: 0 0 auto;
    width: auto;
    max-width: 100%;
}

/* custom style start */
#pdf-download-content .sg_dt__title {
	font-size: 18px;
	font-weight: 500;
	line-height: 28px;
	color: #000;
    border-top: 1px solid #000;
	border-bottom: 1px solid #000;
    padding: 7px 0;
}
#pdf-download-content .sg_dt__info {
    margin-top: 7px;
}
#pdf-download-content .sg_dt__info-item {
	color: #666;
}
#pdf-download-content .sg_dt__reply-list {
    margin-top: 40px;
}
#pdf-download-content .sg_dt__reply + .sg_dt__reply {
    margin-top: 30px;
}
#pdf-download-content .sg_dt__reply-wrap {
    border: 1px solid #ccc;
    border-radius: 4px;
    padding: 20px;
}
#pdf-download-content .sg_dt__reply-head {
    border-bottom: 1px solid #ddd;
    padding-bottom: 7px;
    margin-bottom: 7px;
}
#pdf-download-content .sg_dt__reply-date {
    color: #666;
}
#pdf-download-content .sg_dt__reply-badge {
    display: inline-block;
    font-size: 12px;
    line-height: 22px;
    background-color: #c7ffdb;
    border-radius: 4px;
    padding: 0 7px;
    margin-bottom: 7px;
}
#pdf-download-content .sg_dt__reply-agent .sg_dt__reply-badge {
    background-color: #ffe9de;
}
#pdf-download-content .sg_dt__reply-link-text {
    color: #0bbc5c;
}
#pdf-download-content .sg_dt__reply-link-href {
    color: #3858e9;
}
#pdf-download-content .sg_dt__footer {
    border-top: 1px solid #000;
    margin-top: 50px;
}
#pdf-download-content .sg_dt__field-list {
    margin-top: 7px;
}
#pdf-download-content .sg_dt__field-group {
    border-bottom: 1px solid #ddd;
    margin-top: 14px;
    margin-bottom: 3px;
    padding-bottom: 3px;
}
#pdf-download-content .sg_dt__field {
    color: #666;
}
</style>
