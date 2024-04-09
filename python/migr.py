import pandas as pd
import mysql.connector


db = mysql.connector.connect(user='backend_demo_1', password='password',
                                  host='127.0.0.1',
                                  database='backend', port='37')


dataframe = pd.read_excel("icd.xlsx")
index = 0
cur=db.cursor()
dataframe = dataframe.fillna("null")


for i in range(15038):
    index = i
    uid = str(dataframe.iloc[index]["Уникальный идентификатор"])
    sortfield = str(dataframe.iloc[index]["Поле сортировки"])
    icd_code = str(dataframe.iloc[index]["Код МКБ"])
    name = str(dataframe.iloc[index]["Название"])
    if("'" in name):
        name = name.replace("'",".")
    parentuid = str(dataframe.iloc[index]["Код родительской записи"])
    othercode = str(dataframe.iloc[index]["Дополнительный код"])
    actuality = str(dataframe.iloc[index]["Признак актуальности"])
    date = str(dataframe.iloc[index]["Дата изменения актуальности"])
    date1 = ""
    if(not(pd.isna(dataframe.iloc[index]["Дата изменения актуальности"]))):
        date1 = str(date)
    sql = f"INSERT INTO icd10(id,sorting,mkb_code,mkb_name,idParent,rec_code,actual,date) VALUES ('{uid}','{sortfield}','{icd_code}','{name}','{parentuid}','{othercode}','{actuality}','{date1}')"
    try:
        cur.execute(sql)
        db.commit()
    except:
        print(sql)

