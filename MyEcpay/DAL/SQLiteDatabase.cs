using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Data.SQLite;
using System.Data;

namespace Ecpay.DAL
{


    class SQLiteDatabase
    {
        //create table if not exists

        private String _createTableSql = Ecpay.Common.SystemLang.DB_CREATE_DATA_TABLE_SQL_STRING;
        String dbConnection;


        public static SQLiteDatabase GetInstance()
        {
            return new SQLiteDatabase();
        }

        private SQLiteDatabase()
        {
            dbConnection = "Data Source=" + Ecpay.Common.SystemLang.APP_BASE_DATA_DATABASE_NAME;
            this.ExecuteNonQuery(this._createTableSql);

        }

        public DataSet GetDataTable(string sql)
        {
            using (SQLiteConnection connection = new SQLiteConnection(dbConnection))
            {
                DataSet ds = new DataSet();
                try
                {
                    connection.Open();
                    SQLiteDataAdapter command = new SQLiteDataAdapter(sql, connection);
                    command.Fill(ds, "ds");
                }
                catch (System.Data.SQLite.SQLiteException ex)
                {
                    throw new Exception(ex.Message);
                }
                return ds;
            }
        }

        /// <summary>
        /// 
        /// </summary>
        /// <param name="sql"></param>
        /// <returns></returns>
        public int ExecuteNonQuery(string sql)
        {

            using (SQLiteConnection cnn = new SQLiteConnection(dbConnection))
            {
                cnn.Open();
                using (SQLiteCommand mycommand = new SQLiteCommand(cnn))
                {
                    mycommand.CommandText = sql;
                    int rowsUpdated = mycommand.ExecuteNonQuery();
                    cnn.Close();
                    return rowsUpdated;
                }

            }


        }


        /// <summary>
        /// 
        /// </summary>
        /// <param name="sql"></param>
        /// <returns></returns>
        public string ExecuteScalar(string sql)
        {

            using (SQLiteConnection cnn = new SQLiteConnection(dbConnection))
            {
                cnn.Open();

                using (SQLiteCommand mycommand = new SQLiteCommand(cnn))
                {
                    mycommand.CommandText = sql;
                    object value = mycommand.ExecuteScalar();
                    cnn.Close();
                    if (value != null)
                    {
                        return value.ToString();
                    }
                    return "";
                }
            }


        }


        /// <summary>
        /// 
        /// </summary>
        /// <param name="tableName"></param>
        /// <param name="data"></param>
        /// <param name="where"></param>
        /// <returns></returns>
        public bool Update(String tableName, Dictionary<String, String> data, String where)
        {

            String vals = "";
            Boolean returnCode = true;
            if (data.Count >= 1)
            {
                foreach (KeyValuePair<String, String> val in data)
                {
                    vals += String.Format(" {0} = '{1}',", val.Key.ToString(), val.Value.ToString());
                }
                vals = vals.Substring(0, vals.Length - 1);
            }
            try
            {
                this.ExecuteNonQuery(String.Format("update {0} set {1} where {2};", tableName, vals, where));
            }
            catch
            {
                returnCode = false;
            }
            return returnCode;

        }


        /// <summary>
        /// 
        /// </summary>
        /// <param name="tableName"></param>
        /// <param name="data"></param>
        /// <returns></returns>
        public bool Insert(String tableName, Dictionary<String, String> data)
        {
            Boolean ex_tag = false;
            if (data.Count <= 0)
            {
                return ex_tag;
            }
            else
            {
                try
                {
                    String values = "";
                    String lieName = "";
                    foreach (KeyValuePair<String, String> item in data)
                    {
                        values += String.Format("'{0}',", item.Value.ToString());
                        lieName += String.Format("{0},", item.Key.ToString());
                    }
                    values = values.Substring(0, values.Length - 1);
                    lieName = lieName.Substring(0, lieName.Length - 1);

                    String sql = String.Format("Insert into {0}({1}) values({2})", tableName, lieName, values);
                    if (this.ExecuteNonQuery(sql) == 1)
                    {
                        ex_tag = true;
                    }
                }
                catch (Exception ex)
                {
                    return false;
                }
            }

            return ex_tag;
        }

        /// <summary>
        /// 
        /// </summary>
        /// <param name="tableName"></param>
        /// <param name="where"></param>
        /// <returns></returns>
        public bool IsExist(String tableName, KeyValuePair<String, String> where)
        {
            try
            {
                String sql = "Select {0} from {1} where {2}='{3}';";
                sql = String.Format(sql, where.Key, tableName, where.Key, where.Value);
                String returndata = this.ExecuteScalar(sql);
                if (where.Value != returndata)
                {
                    return false;
                }
                else
                {
                    return true;
                }
            }
            catch (Exception ex)
            {
                throw new Exception(ex.Message);
            }
        }
    }
}
