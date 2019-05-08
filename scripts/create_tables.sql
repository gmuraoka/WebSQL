create table Customers (
   Id                   int                 ,
   FirstName            varchar(40)         not null,
   LastName             varchar(40)         not null,
   City                 varchar(40)         null,
   Country              varchar(40)         null,
   Phone                varchar(20)         null
);~
create table 'Orders' (
   Id                   int                 ,
   OrderDate            datetime             not null,
   OrderNumber          varchar(10)          null,
   CustomerId           int                  not null,
   TotalAmount          decimal(12,2)        null default 0
);~
create table OrderItems (
   Id                   int                  ,
   OrderId              int                  not null,
   ProductId            int                  not null,
   UnitPrice            decimal(12,2)        not null default 0,
   Quantity             int                  not null default 1
);~
create table Products (
   Id                   int                 ,
   ProductName          varchar(50)         not null,
   SupplierId           int                  not null,
   UnitPrice            decimal(12,2)        null default 0,
   Package              varchar(30)         null,
   IsDiscontinued       bit                  not null default 0
);~
create table Suppliers (
   Id                   int                 ,
   CompanyName          varchar(40)         not null,
   ContactName          varchar(50)         null,
   ContactTitle         varchar(40)         null,
   City                 varchar(40)         null,
   Country              varchar(40)         null,
   Phone                varchar(30)         null,
   Fax                  varchar(30)         null
);
