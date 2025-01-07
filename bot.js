const { Client, GatewayIntentBits } = require("discord.js");
const axios = require("axios");
require("dotenv").config();

const client = new Client({
  intents: [
    GatewayIntentBits.Guilds,
    GatewayIntentBits.GuildMessages,
    GatewayIntentBits.MessageContent,
  ],
});

// Event ketika bot online
client.once("ready", () => {
  console.log(`Bot ${client.user.tag} siap!`);
});

// Event untuk menangani pesan
client.on("messageCreate", async (message) => {
  if (message.author.bot) return;

  const data = {
    userId: message.author.id,
    username: message.author.username,
    content: message.content,
    channelId: message.channel.id,
    guildId: message.guild.id,
  };

  try {
    let $datas = await axios.post(
      "https://crazyones-three.vercel.app/createMessage",
      data
    );
    const replyMessage = $datas.data || "Server did not respond.";
    await message.reply(replyMessage);
  } catch (error) {
    console.error("Error:", error.message);
    console.error("Details:", error.response?.data || error);

    if (
      error.response?.status === 400 &&
      /sensitive content/i.test(error.response?.data || "")
    ) {
      await message.reply(
        "The command entered may contain sensitive content and cannot be processed.."
      );
    } else {
      await message.reply("Bot server error. Please try again later..");
    }
  }
});

client.login(process.env.DISCORD_TOKEN);
