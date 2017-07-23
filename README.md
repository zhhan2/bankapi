# bankapi
This is a practice project using PHP to build a set of API to simulate the functionality of a basic bank account.

API List:

POST /account/create
{
  name: String,
  id: String
}

POST /account/close
{
  accountId: String
}

POST /account/withdraw
{
  accountId: String,
  amount: Number // positive
}

POST /account/deposit
{
  accountId: String,
  amount: Number // positive
}

POST /account/transfer
{
  fromAccountId: String,
  toAccountId: String,
  amount: Number // positive
}
